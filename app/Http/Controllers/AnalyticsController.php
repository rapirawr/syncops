<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SyntheticBenchmark;
use App\Services\BenchmarkRunnerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $hours = (int) $request->get('window', 24);
        if (!in_array($hours, [24, 168, 720])) {
            $hours = 24;
        }

        $projects = Project::with(['recentMetricsSnapshots', 'latestSyntheticBenchmark'])
            ->orderBy('name')
            ->get();

        $scorecards = $projects->map(function ($project) use ($hours) {
            $sla = $project->getSlaMetrics($hours);
            $latestBenchmark = $project->latestSyntheticBenchmark;

            return [
                'project' => $project,
                'sla' => $sla,
                'latest_benchmark' => $latestBenchmark,
            ];
        });

        // Global aggregates
        $totalProjects = $projects->count();
        $globalUptimeAvg = $scorecards->avg(fn($s) => $s['sla']['uptime_percent']) ?? 100.0;
        $globalAvgLatency = (int) round($scorecards->avg(fn($s) => $s['sla']['avg_latency_ms']) ?? 0);
        $metSlaCount = $scorecards->filter(fn($s) => in_array($s['sla']['grade'], ['A+', 'A']))->count();
        $slaComplianceRate = $totalProjects > 0 ? round(($metSlaCount / $totalProjects) * 100, 1) : 100.0;

        // Timeline trends data for Chart.js
        $since = now()->subHours($hours);
        $recentBenchmarks = SyntheticBenchmark::with('project')
            ->where('executed_at', '>=', $since)
            ->latest('executed_at')
            ->take(15)
            ->get();

        return view('analytics.index', compact(
            'scorecards',
            'hours',
            'totalProjects',
            'globalUptimeAvg',
            'globalAvgLatency',
            'slaComplianceRate',
            'recentBenchmarks'
        ));
    }

    public function runBenchmark(Request $request, Project $project, BenchmarkRunnerService $runner)
    {
        $concurrency = (int) $request->input('concurrency', 5);
        $requests = (int) $request->input('requests', 20);

        try {
            $benchmark = $runner->run($project, $concurrency, $requests);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Synthetic HTTP benchmark completed successfully.',
                    'benchmark' => [
                        'id' => $benchmark->id,
                        'project_name' => $project->name,
                        'total_requests' => $benchmark->total_requests,
                        'successful_requests' => $benchmark->successful_requests,
                        'failed_requests' => $benchmark->failed_requests,
                        'min_latency_ms' => $benchmark->min_latency_ms,
                        'max_latency_ms' => $benchmark->max_latency_ms,
                        'avg_latency_ms' => $benchmark->avg_latency_ms,
                        'p50_latency_ms' => $benchmark->p50_latency_ms,
                        'p90_latency_ms' => $benchmark->p90_latency_ms,
                        'p99_latency_ms' => $benchmark->p99_latency_ms,
                        'status_codes' => $benchmark->status_codes,
                        'executed_at' => $benchmark->executed_at->format('Y-m-d H:i:s'),
                    ]
                ]);
            }

            return redirect()->back()->with('success', "Benchmark selesai untuk {$project->name}. Score: " . ($benchmark->score ?: 'A+') . " (Avg Latency: {$benchmark->avg_latency_ms}ms)");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to execute benchmark: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menjalankan benchmark: ' . $e->getMessage());
        }
    }

    public function runAllBenchmarks(Request $request, BenchmarkRunnerService $runner)
    {
        $concurrency = (int) $request->input('concurrency', 5);
        $requests = (int) $request->input('requests', 15);

        $projects = Project::orderBy('name')->get();
        $results = [];

        foreach ($projects as $project) {
            try {
                $bm = $runner->run($project, $concurrency, $requests);
                $results[] = [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'success' => true,
                    'p50_latency_ms' => $bm->p50_latency_ms,
                    'p90_latency_ms' => $bm->p90_latency_ms,
                    'p99_latency_ms' => $bm->p99_latency_ms,
                    'successful_requests' => $bm->successful_requests,
                    'total_requests' => $bm->total_requests,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Batch synthetic HTTP benchmarks completed for all projects.',
            'total_tested' => count($results),
            'results' => $results,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $hours = (int) $request->get('window', 24);
        $projects = Project::orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="telemetry_sla_report_' . date('Y-m-d_H-i') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($projects, $hours) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Project Name', 'Stage', 'SLA Grade', 'Uptime %', 'Avg Latency (ms)', 'Error Rate %', 'SLA Compliance Status', 'Checked Window']);

            foreach ($projects as $project) {
                $sla = $project->getSlaMetrics($hours);
                fputcsv($file, [
                    $project->name,
                    str_replace('_', ' ', $project->status),
                    $sla['grade'],
                    $sla['uptime_percent'] . '%',
                    $sla['avg_latency_ms'],
                    $sla['error_rate'] . '%',
                    $sla['status'],
                    "Last {$hours} Hours"
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
