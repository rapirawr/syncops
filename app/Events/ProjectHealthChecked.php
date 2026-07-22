<?php

namespace App\Events;

use App\Models\MetricsSnapshot;
use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectHealthChecked
{
    use Dispatchable, SerializesModels;

    /**
     * Fired after an uptime check saves a new metrics snapshot.
     */
    public function __construct(
        public Project $project,
        public MetricsSnapshot $snapshot,
        public string $oldStatus,
    ) {
    }
}
