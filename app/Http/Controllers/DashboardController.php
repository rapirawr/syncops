<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\MetricsSnapshot;
use App\Models\ProjectUpdate;
use App\Services\ServerMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the dashboard shell. Renders instantly with skeletons;
     * stats/telemetry/projects load via the fragment endpoints below.
     */
    public function index(Request $request)
    {
        // Cheap data only — everything heavy is deferred to fragments
        $categories = Cache::remember('dashboard_categories', 120, function () {
            return Project::whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category')
                ->map(fn($c) => is_string($c) ? $c : (string)$c)
                ->filter()
                ->values()
                ->all();
        });

        // Exact skeleton count for the projects section (fast COUNT query)
        $skeletonCount = min(Project::count(), 9);

        return view('dashboard', compact('categories', 'skeletonCount'));
    }

    /**
     * Fragment: top alert bar + KPI stat widgets (HTML).
     */
    public function fragmentStats(Request $request)
    {
        $projects = $this->filteredProjects($request);
        $stats = $this->computeStats($projects);

        return view('dashboard._stats', compact('projects', 'stats'));
    }

    /**
     * Fragment: host hardware telemetry (HTML).
     * The slow PowerShell probing lives ONLY here now, off the critical path.
     */
    public function fragmentTelemetry(ServerMetricsService $metricsService)
    {
        $serverMetrics = $metricsService->getMetrics();

        return view('dashboard._telemetry', compact('serverMetrics'));
    }

    /**
     * Fragment: project table + card grid (HTML), honoring the same
     * search/status/category/sort query params as the old full page.
     */
    public function fragmentProjects(Request $request)
    {
        $projects = $this->filteredProjects($request);

        return view('dashboard._projects', compact('projects'));
    }

    /**
     * Shared query builder for dashboard project listing (filters + sort).
     */
    protected function filteredProjects(Request $request)
    {
        $query = Project::with(['latestMetricsSnapshot', 'latestGithubSnapshot', 'statusOverrides', 'recentMetricsSnapshots', 'latestAiInsight'])
            ->orderBy('order');

        // Search by name/description
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by development status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $projects = $query->get();

        $sort = $request->get('sort', 'custom');

        // Health sort: worst health status first
        if ($sort === 'health') {
            return $projects->sortBy(function ($project) {
                return match ($project->runtime_status) {
                    'unreachable' => 1,
                    'critical' => 2,
                    'warning' => 3,
                    'maintenance' => 4,
                    'healthy' => 5,
                    default => 6
                };
            })->values();
        }

        // Alphabetical sort: by project name
        if ($sort === 'alphabetical') {
            return $projects->sortBy('name')->values();
        }

        // Default 'custom' sort: uses database orderBy('order')
        return $projects;
    }

    /**
     * Aggregate health stats for the KPI widgets.
     */
    protected function computeStats($projects): array
    {
        return [
            'total' => $projects->count(),
            'healthy' => $projects->filter(fn($p) => $p->runtime_status === 'healthy')->count(),
            'warning' => $projects->filter(fn($p) => $p->runtime_status === 'warning')->count(),
            'critical' => $projects->filter(fn($p) => in_array($p->runtime_status, ['critical', 'unreachable']))->count(),
        ];
    }

    /**
     * Display the global audit and system log viewer.
     */
    public function logs(Request $request)
    {
        $metricsLogs = MetricsSnapshot::with('project')
            ->latest('checked_at')
            ->limit(100)
            ->get();

        $statusLogs = ProjectUpdate::with('project')
            ->latest('created_at')
            ->limit(100)
            ->get();

        return view('logs', compact('metricsLogs', 'statusLogs'));
    }
}
