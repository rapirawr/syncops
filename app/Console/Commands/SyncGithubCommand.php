<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Jobs\SyncGithubDataJob;
use Illuminate\Console\Command;

class SyncGithubCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:sync-github';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to sync GitHub metadata and last commits for projects';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projects = Project::where('status', 'done')
            ->whereNotNull('repo_owner')
            ->whereNotNull('repo_name')
            ->get();

        if ($projects->isEmpty()) {
            $this->info("No projects with repo owner and name found.");
            return;
        }

        foreach ($projects as $project) {
            SyncGithubDataJob::dispatch($project);
            $this->info("Dispatched GitHub sync job for project: {$project->name}");
        }

        $this->info("GitHub sync jobs successfully dispatched for " . $projects->count() . " projects.");
    }
}
