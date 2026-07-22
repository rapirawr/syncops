<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Jobs\CheckProjectHealthJob;
use Illuminate\Console\Command;

class CheckHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:check-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to check health metrics and uptime for all active projects';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projects = Project::where('status', 'done')
            ->where(function ($query) {
                $query->whereNotNull('metrics_endpoint')
                      ->orWhereNotNull('live_url');
            })->get();

        if ($projects->isEmpty()) {
            $this->info("No active projects found with monitoring URLs.");
            return;
        }

        foreach ($projects as $project) {
            CheckProjectHealthJob::dispatch($project);
            $this->info("Dispatched health check job for project: {$project->name}");
        }

        $this->info("Health check jobs successfully dispatched for " . $projects->count() . " projects.");
    }
}
