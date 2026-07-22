<?php

namespace App\Listeners;

use App\Events\ProjectHealthChecked;
use App\Jobs\AnalyzeProjectHealthJob;
use Illuminate\Support\Facades\Cache;

class TriggerAiHealthAnalysis
{
    /**
     * Minimum minutes between AI analyses per project (cost control).
     */
    public const RATE_LIMIT_MINUTES = 5;

    /**
     * Queue an AI health analysis after an uptime check, rate-limited per project.
     */
    public function handle(ProjectHealthChecked $event): void
    {
        if (empty(config('services.nvidia.key'))) {
            return; // AI is optional — no key, no work
        }

        $statusChanged = $event->oldStatus !== $event->snapshot->health_status;

        // Cache::add is atomic: returns false if the key exists (within rate window).
        // A status CHANGE always gets analyzed (bypasses the limit) so incidents
        // and recoveries are never missed; steady-state checks are throttled.
        $lockKey = "ai_analysis_rate_limit:{$event->project->id}";
        $acquired = Cache::add($lockKey, now()->timestamp, now()->addMinutes(self::RATE_LIMIT_MINUTES));

        if (!$acquired && !$statusChanged) {
            return;
        }

        if ($statusChanged) {
            // Refresh the window so follow-up checks are throttled from now
            Cache::put($lockKey, now()->timestamp, now()->addMinutes(self::RATE_LIMIT_MINUTES));
        }

        AnalyzeProjectHealthJob::dispatch($event->project, 'realtime');
    }
}
