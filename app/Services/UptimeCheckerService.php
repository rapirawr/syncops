<?php

namespace App\Services;

use App\Models\Project;
use App\Models\MetricsSnapshot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UptimeCheckerService
{
    /**
     * Perform a health check on the project and store the snapshot.
     */
    public function check(Project $project): MetricsSnapshot
    {
        $url = $project->metrics_endpoint ?: $project->live_url;

        if (empty($url)) {
            return MetricsSnapshot::create([
                'project_id' => $project->id,
                'health_status' => 'unreachable',
                'error_message' => 'No metrics endpoint or live URL specified.',
                'checked_at' => now(),
            ]);
        }

        $startTime = microtime(true);
        $httpStatus = null;
        $errorMessage = null;
        $responseBody = null;

        // Resolve and store IP address for the live URL
        $liveUrlForIp = $project->live_url ?: $url;
        $parsedHost = parse_url($liveUrlForIp, PHP_URL_HOST);
        if ($parsedHost) {
            $resolved = gethostbyname($parsedHost);
            $ipAddress = ($resolved !== $parsedHost) ? $resolved : null;
            if ($ipAddress && $project->ip_address !== $ipAddress) {
                $project->ip_address = $ipAddress;
                $project->saveQuietly();
            }
        }

        try {
            // Hit the endpoint with project's custom timeout (default 5s)
            $timeout = (int) ($project->timeout_seconds ?: 5);
            $response = Http::timeout($timeout)->get($url);
            $endTime = microtime(true);
            $responseTimeMs = (int)(($endTime - $startTime) * 1000);
            $httpStatus = $response->status();
            $responseBody = $response->body();
        } catch (\Exception $e) {
            $responseTimeMs = null;
            $errorMessage = $e->getMessage();
        }

        // If the endpoint failed to connect / timed out
        if ($httpStatus === null) {
            return MetricsSnapshot::create([
                'project_id' => $project->id,
                'health_status' => 'unreachable',
                'http_status' => null,
                'error_message' => $errorMessage ?: 'Connection timed out or failed.',
                'checked_at' => now(),
            ]);
        }

        // Initialize variables
        $requestsCount = 0;
        $errorsCount = 0;
        $avgResponseTimeMs = 0;
        $isMetricsJson = false;

        // Try parsing metrics JSON if we got a 200 OK
        if ($httpStatus === 200 && !empty($responseBody)) {
            $json = json_decode($responseBody, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json['requests_count'])) {
                $requestsCount = (int)$json['requests_count'];
                $errorsCount = (int)($json['errors_count'] ?? 0);
                $avgResponseTimeMs = (int)($json['avg_response_time_ms'] ?? $responseTimeMs);
                $isMetricsJson = true;
            }
        }

        // Fallback for standard page response (non-JSON or non-metrics)
        if (!$isMetricsJson) {
            $requestsCount = 1;
            $errorsCount = ($httpStatus >= 400) ? 1 : 0;
            $avgResponseTimeMs = $responseTimeMs;
        }

        // Calculate error rate
        $errorRate = $requestsCount > 0 ? ($errorsCount / $requestsCount) * 100 : 0.0;

        // Determine health status based on rules
        $healthStatus = $this->determineStatus($project, $errorRate, $avgResponseTimeMs, $httpStatus);

        return MetricsSnapshot::create([
            'project_id' => $project->id,
            'health_status' => $healthStatus,
            'requests_count' => $requestsCount,
            'errors_count' => $errorsCount,
            'error_rate' => $errorRate,
            'avg_response_time_ms' => $avgResponseTimeMs,
            'http_status' => $httpStatus,
            'checked_at' => now(),
            'error_message' => $httpStatus >= 400 ? "Server returned status code {$httpStatus}" : null,
        ]);
    }

    /**
     * Logic to determine the status of the project.
     */
    public function determineStatus(Project $project, float $errorRate, int $responseTimeMs, int $httpStatus): string
    {
        // If HTTP status is failure/error code
        if ($httpStatus >= 500) {
            return 'critical';
        }

        if ($httpStatus >= 400) {
            return 'warning';
        }

        // Error rate thresholds
        if ($errorRate > 5.0) {
            return 'critical';
        }

        // Check if response time is significantly higher than historical average
        $isSlow = $this->checkIsResponseTimeAnomaly($project, $responseTimeMs);

        if ($errorRate >= 1.0 || $isSlow) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Check if response time is > 2.0x of the historical average (min 3 checks, threshold > 200ms)
     */
    protected function checkIsResponseTimeAnomaly(Project $project, int $currentResponseTimeMs): bool
    {
        // Query the last 10 successful snapshots
        $history = $project->metricsSnapshots()
            ->whereNotNull('avg_response_time_ms')
            ->where('health_status', '!=', 'unreachable')
            ->latest('checked_at')
            ->limit(10)
            ->get();

        if ($history->count() < 3) {
            return false;
        }

        $avgHistory = $history->avg('avg_response_time_ms');

        // Check if current response time is double the history average and > 200ms
        return $currentResponseTimeMs > ($avgHistory * 2.0) && $currentResponseTimeMs > 200;
    }
}
