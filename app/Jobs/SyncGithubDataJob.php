<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\GithubSnapshot;
use App\Services\GithubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGithubDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected $project;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Execute the job.
     */
    public function handle(GithubService $githubService): void
    {
        if ($this->project->status !== 'done') {
            Log::info("Skipping GitHub sync for project: {$this->project->name} (Development stage is '{$this->project->status}', not 'done').");
            return;
        }

        if (empty($this->project->repo_owner) || empty($this->project->repo_name)) {
            Log::info("Project {$this->project->name} does not have repository owner/name set. Skipping Github Sync.");
            return;
        }

        Log::info("Syncing GitHub data for project: {$this->project->name}");

        $data = $githubService->fetchRepoData($this->project->repo_owner, $this->project->repo_name);

        if ($data) {
            // Detect first-ever sync BEFORE updateOrCreate
            $isFirstSync = !GithubSnapshot::where('project_id', $this->project->id)->exists();

            GithubSnapshot::updateOrCreate(
                ['project_id' => $this->project->id],
                [
                    'default_branch'        => $data['default_branch'],
                    'last_commit_sha'       => $data['last_commit_sha'],
                    'last_commit_message'   => $data['last_commit_message'],
                    'last_commit_at'        => $data['last_commit_at'],
                    'open_issues_count'     => $data['open_issues_count'],
                    'open_prs_count'        => $data['open_prs_count'],
                    'stars_count'           => $data['stars_count'],
                    'detected_technologies' => $data['detected_technologies'] ?? [],
                    'synced_at'             => now(),
                ]
            );

            Log::info("Successfully synced GitHub data for project: {$this->project->name}");

            // First sync of a new project → AI profile (description + tech tags)
            if ($isFirstSync) {
                GenerateProjectSummaryJob::dispatch($this->project);
            }
        }
    }
}
