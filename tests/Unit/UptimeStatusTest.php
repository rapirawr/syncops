<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Services\UptimeCheckerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UptimeStatusTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test status determination logic on various metric payloads.
     */
    public function test_determine_status_logic(): void
    {
        $service = new UptimeCheckerService();
        $project = new Project();

        // 1. Test server internal error
        $status = $service->determineStatus($project, 0.0, 150, 500);
        $this->assertEquals('critical', $status);

        // 2. Test page not found or auth error
        $status = $service->determineStatus($project, 0.0, 100, 404);
        $this->assertEquals('warning', $status);

        // 3. Test high error rate (> 5%)
        $status = $service->determineStatus($project, 8.5, 120, 200);
        $this->assertEquals('critical', $status);

        // 4. Test warning error rate (between 1% and 5%)
        $status = $service->determineStatus($project, 2.5, 90, 200);
        $this->assertEquals('warning', $status);

        // 5. Test normal conditions (no anomalies)
        $status = $service->determineStatus($project, 0.0, 150, 200);
        $this->assertEquals('healthy', $status);
    }
}
