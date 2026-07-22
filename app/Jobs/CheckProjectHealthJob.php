<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Services\UptimeCheckerService;
use App\Events\ProjectHealthChecked;
use App\Notifications\ProjectStatusAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckProjectHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;

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
    public function handle(UptimeCheckerService $checkerService): void
    {
        // Skip health check if project status is not 'done'
        if ($this->project->status !== 'done') {
            Log::info("Skipping health check for project: {$this->project->name} (Development stage is '{$this->project->status}', not 'done').");
            return;
        }

        // 1. Get the old metrics status
        $oldStatus = $this->project->latestMetricsSnapshot?->health_status ?? 'healthy';

        Log::info("Running health check for: {$this->project->name} (Old status: {$oldStatus})");

        // 2. Perform the status check (which saves the snapshot to metrics_snapshots)
        $snapshot = $checkerService->check($this->project);
        $newStatus = $snapshot->health_status;

        Log::info("Health check complete for: {$this->project->name} (New status: {$newStatus})");

        // Notify the AI Ops layer (listener rate-limits & queues the analysis)
        ProjectHealthChecked::dispatch($this->project, $snapshot, $oldStatus);

        // 3. Trigger notification if status changes
        $shouldNotify = false;
        $isRecovery = false;

        if ($oldStatus === 'healthy' && in_array($newStatus, ['warning', 'critical', 'unreachable'])) {
            $shouldNotify = true;
        } elseif ($oldStatus !== 'healthy' && $newStatus === 'healthy') {
            $shouldNotify = true;
            $isRecovery = true;
        }

        if ($shouldNotify) {
            $admins = User::all();
            if ($admins->isEmpty()) {
                Log::warning("No users/admins found in the database. Cannot send health check notification.");
            } else {
                foreach ($admins as $admin) {
                    $admin->notify(new ProjectStatusAlertNotification($this->project, $snapshot, $oldStatus, $isRecovery));
                }
                Log::info("Sent project status notification to admin(s) for {$this->project->name}.");
            }
        }
    }
}
