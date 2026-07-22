<?php

namespace App\Jobs;

use App\Models\AiInsight;
use App\Models\Project;
use App\Services\AIOpsService;
use App\Services\GithubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * After the first GitHub sync of a new project: read README + dependency
 * manifests and let AI generate a short description + validated tech tags.
 */
class GenerateProjectSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 90;

    public function __construct(protected Project $project)
    {
    }

    public function handle(AIOpsService $ai, GithubService $github): void
    {
        if (!$ai->isConfigured()) {
            return;
        }

        if (empty($this->project->repo_owner) || empty($this->project->repo_name)) {
            return;
        }

        $files = $github->fetchRepoFilesForSummary($this->project->repo_owner, $this->project->repo_name);

        if (empty($files)) {
            Log::info("GenerateProjectSummaryJob: no repo files fetched for {$this->project->name}, skipping.");
            return;
        }

        $snapshot = $this->project->latestGithubSnapshot;
        $existingTechs = $snapshot?->detected_technologies ?? [];

        $summary = $ai->generateProjectSummary($this->project, $files, $existingTechs);

        if (!$summary) {
            return;
        }

        // Only fill the description if the user hasn't written one themselves
        if (!empty($summary['description']) && empty($this->project->description)) {
            $this->project->description = $summary['description'];
            $this->project->saveQuietly();
        }

        // Merge validated tech tags into the snapshot
        if (!empty($summary['technologies']) && $snapshot) {
            $techs = collect($summary['technologies'])
                ->map(fn ($t) => strtolower(trim((string) $t)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($techs)) {
                $snapshot->update(['detected_technologies' => $techs]);
            }
        }

        // Record as an info insight so it shows up in the AI history
        AiInsight::create([
            'project_id' => $this->project->id,
            'type' => 'summary',
            'severity' => 'info',
            'title' => 'Project profile generated',
            'content' => $summary['description'] ?? 'Tech stack tags validated from repository files.',
            'generated_at' => now(),
        ]);

        Log::info("GenerateProjectSummaryJob: summary generated for {$this->project->name}.");
    }
}
