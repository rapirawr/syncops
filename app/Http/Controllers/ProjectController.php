<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Jobs\SyncGithubDataJob;
use App\Jobs\CheckProjectHealthJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return redirect()->route('dashboard')->with('open_create_modal', true);
    }

    /**
     * Store a newly created project in database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'repo_link' => 'nullable|string|max:255',
            'live_url' => 'nullable|url|max:255',
            'status' => 'required|in:planning,in_progress,on_hold,done,archived',
            'metrics_endpoint' => 'nullable|url|max:255',
            'timeout_seconds' => 'nullable|integer|min:1|max:60',
            'progress' => 'required|integer|min:0|max:100',
            'category' => 'nullable|string|max:255',
        ]);

        if (!empty($validated['repo_link'])) {
            $parsed = $this->parseRepoLink($validated['repo_link']);
            if (empty($parsed['repo_owner']) || empty($parsed['repo_name'])) {
                return back()->withErrors(['repo_link' => 'Format link repository Git tidak valid. Gunakan format seperti https://github.com/owner/repo'])->withInput();
            }
            $validated['repo_owner'] = $parsed['repo_owner'];
            $validated['repo_name'] = $parsed['repo_name'];
        } else {
            $validated['repo_owner'] = null;
            $validated['repo_name'] = null;
        }
        unset($validated['repo_link']);

        $validated['order'] = (Project::max('order') ?? 0) + 1;

        $project = Project::create($validated);

        // Record initial status in history
        ProjectUpdate::create([
            'project_id' => $project->id,
            'old_status' => null,
            'new_status' => $project->status,
            'note' => 'Project initialized.',
        ]);

        // Trigger initial checks in background queue if project status is 'done'
        if ($project->status === 'done') {
            if (!empty($project->repo_owner) && !empty($project->repo_name)) {
                SyncGithubDataJob::dispatch($project);
            }
            if (!empty($project->metrics_endpoint) || !empty($project->live_url)) {
                CheckProjectHealthJob::dispatch($project);
            }
        }

        return redirect()->route('dashboard')->with('success', 'Project created successfully.');    }

    /**
     * Display the project detail page.
     */
    public function show(Project $project)
    {
        $project->load([
            'latestGithubSnapshot',
            'latestMetricsSnapshot',
            'activeOverride',
            'recentMetricsSnapshots',
            'latestAiInsight',
            'syntheticBenchmarks',
        ]);

        // Timeline history
        $updates = $project->updates()->orderBy('created_at', 'desc')->get();

        // Uptime history (last 30 snapshots for Chart.js)
        $metricsHistory = $project->metricsSnapshots()
            ->latest('checked_at')
            ->limit(30)
            ->get()
            ->reverse(); // chronological order for chart

        // Uptime failures/incidents history
        $incidents = $project->metricsSnapshots()
            ->whereIn('health_status', ['critical', 'unreachable'])
            ->latest('checked_at')
            ->limit(50)
            ->get();

        $slaMetrics = $project->getSlaMetrics(24);

        $syntheticHistory = $project->syntheticBenchmarks()
            ->latest('executed_at')
            ->limit(20)
            ->get();

        $visitorStats = [
            'total_views' => $project->visitorLogs()->count(),
            'unique_visitors' => $project->visitorLogs()->distinct('visitor_id')->count('visitor_id'),
            'active_visitors' => $project->active_visitors,
            'recent_logs' => $project->visitorLogs()->latest('created_at')->limit(15)->get(),
        ];

        return view('projects.show', compact(
            'project',
            'updates',
            'metricsHistory',
            'incidents',
            'slaMetrics',
            'syntheticHistory',
            'visitorStats'
        ));
    }

    /**
     * Store a manual release note / update log for the project.
     */
    public function storeUpdate(Request $request, Project $project)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $project->updates()->create([
            'old_status' => $project->status,
            'new_status' => $project->status,
            'note' => $validated['note'],
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Catatan rilis / pembaruan berhasil ditambahkan.');
    }

    /**
     * Live Ping HTTP tester endpoint.
     */
    public function ping(Project $project)
    {
        $targetUrl = $project->metrics_endpoint ?: $project->live_url;

        if (empty($targetUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Tautan endpoint atau URL publik belum dikonfigurasi untuk project ini.',
            ], 422);
        }

        $startTime = microtime(true);
        try {
            $response = Http::timeout($project->timeout_seconds ?: 5)->get($targetUrl);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $headers = [];
            foreach ($response->headers() as $key => $values) {
                $headers[$key] = implode(', ', $values);
            }

            return response()->json([
                'success' => true,
                'status_code' => $response->status(),
                'latency_ms' => $latency,
                'headers' => array_slice($headers, 0, 8),
                'content_type' => $response->header('Content-Type') ?: 'Unknown',
                'body_snippet' => Str::limit(strip_tags($response->body()), 250),
                'checked_at' => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success' => false,
                'status_code' => 0,
                'latency_ms' => $latency,
                'message' => $e->getMessage(),
                'checked_at' => now()->format('H:i:s'),
            ], 500);
        }
    }

    /**
     * Show the form for editing the project.
     */
    public function edit(Project $project)
    {
        return redirect()->route('dashboard')->with('open_edit_modal', $project->id);
    }

    /**
     * Return project data as JSON for the edit modal.
     */
    public function editData(Project $project)
    {
        $repoLink = null;
        if (!empty($project->repo_owner) && !empty($project->repo_name)) {
            $repoLink = "https://github.com/{$project->repo_owner}/{$project->repo_name}";
        }

        return response()->json([
            'id'               => $project->id,
            'name'             => $project->name,
            'category'         => $project->category,
            'description'      => $project->description,
            'live_url'         => $project->live_url,
            'metrics_endpoint' => $project->metrics_endpoint,
            'timeout_seconds'  => $project->timeout_seconds ?: 5,
            'repo_link'        => $repoLink,
            'status'           => $project->status,
            'progress'         => $project->progress,
            'update_url'       => route('projects.update', $project),
            'destroy_url'      => route('projects.destroy', $project),
        ]);
    }

    /**
     * Update the project details.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'repo_link' => 'nullable|string|max:255',
            'live_url' => 'nullable|url|max:255',
            'status' => 'required|in:planning,in_progress,on_hold,done,archived',
            'metrics_endpoint' => 'nullable|url|max:255',
            'timeout_seconds' => 'nullable|integer|min:1|max:60',
            'progress' => 'required|integer|min:0|max:100',
            'category' => 'nullable|string|max:255',
            'status_note' => 'nullable|string',
        ]);

        if (!empty($validated['repo_link'])) {
            $parsed = $this->parseRepoLink($validated['repo_link']);
            if (empty($parsed['repo_owner']) || empty($parsed['repo_name'])) {
                return back()->withErrors(['repo_link' => 'Format link repository Git tidak valid. Gunakan format seperti https://github.com/owner/repo'])->withInput();
            }
            $validated['repo_owner'] = $parsed['repo_owner'];
            $validated['repo_name'] = $parsed['repo_name'];
        } else {
            $validated['repo_owner'] = null;
            $validated['repo_name'] = null;
        }
        unset($validated['repo_link']);

        $oldStatus = $project->status;
        $newStatus = $validated['status'];

        $project->update($validated);

        // Record status change if status changed
        if ($oldStatus !== $newStatus) {
            ProjectUpdate::create([
                'project_id' => $project->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $request->input('status_note') ?: 'Status updated via project edit form.',
            ]);
        }

        // Trigger manual queue updates if settings changed and project is done
        if ($project->status === 'done') {
            if (!empty($project->repo_owner) && !empty($project->repo_name)) {
                SyncGithubDataJob::dispatch($project);
            }
            if (!empty($project->metrics_endpoint) || !empty($project->live_url)) {
                CheckProjectHealthJob::dispatch($project);
            }
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project updated successfully.');
    }

    /**
     * Delete the project.
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('dashboard')->with('success', 'Project deleted successfully.');
    }

    /**
     * Apply a status override.
     */
    public function storeOverride(Request $request, Project $project)
    {
        $validated = $request->validate([
            'forced_status' => 'required|in:healthy,warning,critical,maintenance',
            'reason' => 'nullable|string|max:1000',
            'duration_hours' => 'nullable|integer|min:1',
        ]);

        $activeUntil = $validated['duration_hours'] 
            ? now()->addHours($validated['duration_hours']) 
            : null;

        // Clear existing active overrides first
        $project->statusOverrides()->delete();

        $project->statusOverrides()->create([
            'forced_status' => $validated['forced_status'],
            'reason' => $validated['reason'],
            'active_until' => $activeUntil,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Status override applied successfully.');
    }

    /**
     * Clear all status overrides.
     */
    public function clearOverride(Project $project)
    {
        $project->statusOverrides()->delete();
        return redirect()->route('projects.show', $project)->with('success', 'Overrides cleared successfully.');
    }

    /**
     * Trigger manual sync tasks.
     */
    public function sync(Project $project)
    {
        if ($project->status !== 'done') {
            $project->load(['latestMetricsSnapshot', 'latestGithubSnapshot']);
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'skipped' => true,
                    'message' => "Sync skipped: Project status is '" . str_replace('_', ' ', $project->status) . "' (only Done projects are synced).",
                    'status' => $project->runtime_status,
                    'status_reason' => $project->runtime_status_reason,
                    'requests_count' => $project->latestMetricsSnapshot ? number_format($project->latestMetricsSnapshot->requests_count) : '—',
                    'error_rate' => $project->latestMetricsSnapshot ? number_format($project->latestMetricsSnapshot->error_rate, 1) . '%' : '—',
                    'error_rate_raw' => $project->latestMetricsSnapshot ? $project->latestMetricsSnapshot->error_rate : 0,
                    'avg_response_time_ms' => $project->latestMetricsSnapshot && $project->latestMetricsSnapshot->avg_response_time_ms ? $project->latestMetricsSnapshot->avg_response_time_ms . ' ms' : '—',
                    'checked_at' => $project->latestMetricsSnapshot && $project->latestMetricsSnapshot->checked_at ? $project->latestMetricsSnapshot->checked_at->diffForHumans() : 'never',
                ]);
            }

            return redirect()->route('projects.show', $project)->with('warning', "Sync skipped: {$project->name} status is '" . str_replace('_', ' ', $project->status) . "' (only Done projects are synced).");
        }

        // Perform immediate health check for instant telemetry update (~50ms)
        if (!empty($project->metrics_endpoint) || !empty($project->live_url)) {
            CheckProjectHealthJob::dispatchSync($project);
        }

        // Perform SSL check
        if (!empty($project->live_url) || !empty($project->metrics_endpoint)) {
            app(\App\Services\SslCheckerService::class)->checkUrl(
                $project->getSslTargetUrl(),
                $project->timeout_seconds ?? 10
            );
            $this->performSslCheckInternal($project);
        }

        // Dispatch heavy GitHub metadata sync in background queue
        if (!empty($project->repo_owner) && !empty($project->repo_name)) {
            SyncGithubDataJob::dispatch($project);
        }

        // Reload fresh relations after health check
        $project->unsetRelations();
        $project->load(['latestMetricsSnapshot', 'latestGithubSnapshot', 'statusOverrides']);

        if (request()->wantsJson() || request()->ajax()) {
            $recentSnaps = $project->recentMetricsSnapshots->reverse()->values()->map(function($snap) {
                return [
                    'status' => $snap->health_status,
                    'latency' => $snap->avg_response_time_ms,
                    'checked_at' => $snap->checked_at ? $snap->checked_at->format('M d, H:i') : 'Ping Scan',
                ];
            });

            return response()->json([
                'success' => true,
                'status' => $project->runtime_status,
                'status_reason' => $project->runtime_status_reason,
                'requests_count' => $project->latestMetricsSnapshot ? number_format($project->latestMetricsSnapshot->requests_count) : '—',
                'error_rate' => $project->latestMetricsSnapshot ? number_format($project->latestMetricsSnapshot->error_rate, 1) . '%' : '—',
                'error_rate_raw' => $project->latestMetricsSnapshot ? $project->latestMetricsSnapshot->error_rate : 0,
                'avg_response_time_ms' => $project->latestMetricsSnapshot && $project->latestMetricsSnapshot->avg_response_time_ms ? $project->latestMetricsSnapshot->avg_response_time_ms . ' ms' : '—',
                'checked_at' => $project->latestMetricsSnapshot && $project->latestMetricsSnapshot->checked_at ? $project->latestMetricsSnapshot->checked_at->diffForHumans() : 'just now',
                'uptime_percentage' => $project->uptime_percentage,
                'recent_snaps' => $recentSnaps,
            ]);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project health metrics synchronized.');
    }

    /**
     * Persist a new manual card order coming from the dashboard drag-and-drop.
     *
     * Accepts {id, position}[] where position is the visual index within the
     * currently visible (possibly filtered) set. Only the order slots already
     * occupied by those projects are redistributed among them, so projects
     * outside the active filter keep their global positions.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*.id' => 'required|integer|distinct|exists:projects,id',
            'order.*.position' => 'required|integer|min:0',
        ]);

        $items = collect($validated['order'])->sortBy('position')->values();
        $ids = $items->pluck('id');

        DB::transaction(function () use ($items, $ids) {
            $slots = Project::whereIn('id', $ids)
                ->lockForUpdate()
                ->pluck('order')
                ->sort()
                ->values();

            foreach ($items as $index => $item) {
                Project::where('id', $item['id'])->update(['order' => $slots[$index]]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Trigger manual sync tasks for all projects.
     */
    public function syncAll()
    {
        $projects = Project::where('status', 'done')->get();

        // Dispatch all jobs asynchronously (non-blocking)
        foreach ($projects as $project) {
            if (!empty($project->repo_owner) && !empty($project->repo_name)) {
                SyncGithubDataJob::dispatch($project);
            }
            if (!empty($project->metrics_endpoint) || !empty($project->live_url)) {
                CheckProjectHealthJob::dispatch($project);
            }
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'syncing' => true,
                'message' => 'Sync jobs dispatched for ' . $projects->count() . ' projects.',
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Sync jobs dispatched for ' . $projects->count() . ' projects. Data will update shortly.');
    }

    /**
     * Parse a Git repository link into owner and repo name.
     */
    private function parseRepoLink(?string $repoLink): array
    {
        if (empty($repoLink)) {
            return ['repo_owner' => null, 'repo_name' => null];
        }

        $repoLink = trim($repoLink);

        // Match SSH format: git@github.com:owner/repo.git or git@github.com:owner/repo
        if (preg_match('/git@github\.com:([^\/]+)\/([^.]+)(?:\.git)?$/i', $repoLink, $matches)) {
            return [
                'repo_owner' => $matches[1],
                'repo_name' => $matches[2]
            ];
        }

        // Match HTTPS format: https://github.com/owner/repo or similar
        // Strip protocol and www.github.com/
        $cleanUrl = preg_replace('/^(https?:\/\/)?(www\.)?github\.com\//i', '', $repoLink);
        // Strip trailing .git
        $cleanUrl = preg_replace('/\.git$/i', '', $cleanUrl);
        // Trim slashes
        $cleanUrl = trim($cleanUrl, '/');

        $parts = explode('/', $cleanUrl);

        if (count($parts) >= 2) {
            return [
                'repo_owner' => $parts[0],
                'repo_name' => $parts[1]
            ];
        }

        return ['repo_owner' => null, 'repo_name' => null];
    }

    /**
     * Inspect SSL Certificate for a specific project via web route.
     */
    public function checkSsl(Project $project, \App\Services\SslCheckerService $sslChecker)
    {
        $result = $this->performSslCheckInternal($project, $sslChecker);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => ($result['success'] ?? false) ? 'SSL Certificate inspected successfully.' : ($result['error'] ?? 'SSL Check failed.'),
                'ssl' => [
                    'status' => $project->ssl_status,
                    'badge' => $project->ssl_badge,
                    'issuer' => $project->ssl_issuer,
                    'domain' => $project->ssl_domain,
                    'days_left' => $project->ssl_days_left,
                    'valid_from' => $project->ssl_valid_from ? $project->ssl_valid_from->format('M d, Y') : null,
                    'valid_to' => $project->ssl_valid_to ? $project->ssl_valid_to->format('M d, Y') : null,
                    'checked_at' => $project->ssl_last_checked_at ? $project->ssl_last_checked_at->diffForHumans() : 'just now',
                    'error' => $project->ssl_error,
                ],
            ]);
        }

        return redirect()->route('projects.show', $project)->with('success', 'SSL Certificate status updated.');
    }

    /**
     * Internal helper to execute SSL check and update project model.
     */
    public function performSslCheckInternal(Project $project, ?\App\Services\SslCheckerService $sslChecker = null): array
    {
        $sslChecker = $sslChecker ?? app(\App\Services\SslCheckerService::class);
        $targetUrl = $project->getSslTargetUrl();

        if (empty($targetUrl)) {
            $project->update([
                'ssl_status' => 'unsupported',
                'ssl_error' => 'No URL endpoint configured for this project.',
                'ssl_last_checked_at' => now(),
            ]);
            return ['success' => false, 'error' => 'No URL endpoint configured.'];
        }

        $result = $sslChecker->checkUrl($targetUrl, $project->timeout_seconds ?? 10);

        $project->update([
            'ssl_status' => $result['status'] ?? 'unsupported',
            'ssl_issuer' => $result['issuer'] ?? null,
            'ssl_domain' => $result['domain'] ?? null,
            'ssl_valid_from' => $result['valid_from'] ?? null,
            'ssl_valid_to' => $result['valid_to'] ?? null,
            'ssl_days_left' => $result['days_left'] ?? null,
            'ssl_last_checked_at' => now(),
            'ssl_error' => $result['error'] ?? null,
        ]);

        return $result;
    }
}
