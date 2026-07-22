<?php

namespace App\Jobs;

use App\Models\AiInsight;
use App\Services\AIOpsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AI priority ranking of all active warning/critical insights.
 * ShouldBeUnique: bursts of new insights collapse into one ranking run.
 */
class RankAiAlertsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 90;
    public $uniqueFor = 120;

    public function handle(AIOpsService $ai): void
    {
        if (!$ai->isConfigured()) {
            return;
        }

        $alerts = AiInsight::with('project:id,name,category,live_url')
            ->active()
            ->alerts()
            ->latest('generated_at')
            ->limit(25)
            ->get();

        if ($alerts->count() < 2) {
            // Nothing to rank against each other
            $alerts->each(fn ($a) => $a->update(['priority_rank' => 1]));
            return;
        }

        $payload = $alerts->map(function ($a) {
            // History of similar incidents: resolved insights of the same type on this project
            $similarPast = AiInsight::where('project_id', $a->project_id)
                ->where('type', $a->type)
                ->whereNotNull('resolved_at')
                ->count();

            return [
                'id' => $a->id,
                'project' => $a->project?->name,
                'project_category' => $a->project?->category,
                'has_live_url' => !empty($a->project?->live_url), // proxy for "production"
                'severity' => $a->severity,
                'type' => $a->type,
                'title' => $a->title,
                'content' => $a->content,
                'age_minutes' => (int) $a->generated_at->diffInMinutes(now()),
                'similar_past_incidents' => $similarPast,
            ];
        })->all();

        $ranking = $ai->rankAlerts($payload);

        if (empty($ranking)) {
            Log::info('RankAiAlertsJob: AI ranking unavailable, keeping existing order.');
            return;
        }

        $validIds = $alerts->pluck('id')->all();

        foreach ($ranking as $entry) {
            if (!isset($entry['id'], $entry['rank']) || !in_array($entry['id'], $validIds)) {
                continue;
            }

            AiInsight::where('id', $entry['id'])->update([
                'priority_rank' => (int) $entry['rank'],
                'priority_reason' => isset($entry['reason']) ? mb_substr($entry['reason'], 0, 500) : null,
            ]);
        }

        Log::info('RankAiAlertsJob: ranked ' . count($ranking) . ' active alert(s).');
    }
}
