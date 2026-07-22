<?php

namespace App\Jobs;

use App\Models\AiInsight;
use App\Models\Project;
use App\Services\AIOpsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Background AI analysis of one project's health. Never called synchronously
 * from a dashboard request — always dispatched to the queue.
 */
class AnalyzeProjectHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;      // AI analysis is best-effort; the next check re-triggers it
    public $timeout = 120;

    public function __construct(
        protected Project $project,
        protected string $mode = 'realtime', // 'realtime' | 'daily_trend'
    ) {
    }

    public function handle(AIOpsService $ai): void
    {
        // Housekeeping runs regardless of AI availability so stale insights
        // never linger just because the API is down or unconfigured
        $this->autoResolveIfHealthy();
        $this->expireStalePredictions();

        if (!$ai->isConfigured()) {
            return;
        }

        $context = $ai->buildProjectContext($this->project);

        // Nothing to analyze without any metrics history
        if (empty($context['latest_check'])) {
            return;
        }

        $insights = $ai->analyzeProjectHealth($context, $this->mode);

        if (empty($insights)) {
            Log::info("AI analysis ({$this->mode}) for {$this->project->name}: no new insights.");
            return;
        }

        $hasAlert = false;
        $created = 0;

        foreach ($insights as $insight) {
            // Guard against malformed AI output
            if (empty($insight['title']) || empty($insight['content'])) {
                continue;
            }

            $type = in_array($insight['type'] ?? '', ['anomaly', 'root_cause', 'prediction']) ? $insight['type'] : 'anomaly';
            $severity = in_array($insight['severity'] ?? '', ['info', 'warning', 'critical']) ? $insight['severity'] : 'info';

            // Deterministic dedup: the prompt asks the AI not to repeat itself,
            // but do not rely on model compliance — skip if an active insight of
            // the same type with equal-or-higher severity already exists
            if ($this->isDuplicate($type, $severity)) {
                Log::info("AI analysis for {$this->project->name}: skipped duplicate {$type} insight.");
                continue;
            }

            AiInsight::create([
                'project_id' => $this->project->id,
                'type' => $type,
                'severity' => $severity,
                'title' => mb_substr($insight['title'], 0, 255),
                'content' => $insight['content'],
                'generated_at' => now(),
            ]);
            $created++;

            if (in_array($severity, ['warning', 'critical'])) {
                $hasAlert = true;
            }
        }

        Log::info('AI analysis (' . $this->mode . ") for {$this->project->name}: {$created} insight(s) recorded.");

        // New warning/critical insights → re-rank the global alert list
        if ($hasAlert) {
            RankAiAlertsJob::dispatch();
        }
    }

    /**
     * An insight is a duplicate when an active insight of the same type exists
     * within the cooldown window, unless the new one escalates the severity.
     */
    protected function isDuplicate(string $type, string $severity): bool
    {
        $rank = ['info' => 0, 'warning' => 1, 'critical' => 2];

        $existing = AiInsight::where('project_id', $this->project->id)
            ->active()
            ->where('type', $type)
            ->where('generated_at', '>=', now()->subHours(6))
            ->orderByDesc('generated_at')
            ->first();

        if (!$existing) {
            return false;
        }

        // Allow re-reporting only when severity escalated
        return ($rank[$severity] ?? 0) <= ($rank[$existing->severity] ?? 0);
    }

    /**
     * Predictions have no natural "recovered" signal, so expire them after
     * 7 days instead of letting them pile up forever.
     */
    protected function expireStalePredictions(): void
    {
        $expired = AiInsight::where('project_id', $this->project->id)
            ->active()
            ->where('type', 'prediction')
            ->where('generated_at', '<', now()->subDays(7))
            ->update(['resolved_at' => now()]);

        if ($expired > 0) {
            Log::info("Expired {$expired} stale prediction insight(s) for {$this->project->name}.");
        }
    }

    /**
     * When the project is healthy again, auto-resolve its open incident
     * insights (anomaly/root_cause) so stale alerts don't linger on cards.
     */
    protected function autoResolveIfHealthy(): void
    {
        if ($this->project->runtime_status !== 'healthy') {
            return;
        }

        $resolved = AiInsight::where('project_id', $this->project->id)
            ->active()
            ->whereIn('type', ['anomaly', 'root_cause'])
            ->update(['resolved_at' => now()]);

        if ($resolved > 0) {
            Log::info("Auto-resolved {$resolved} AI insight(s) for recovered project {$this->project->name}.");
        }
    }
}
