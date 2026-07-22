<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\Project;

/**
 * Executes the database-backed tools exposed to the AI model in the chat flow.
 * Every tool returns real query results — the AI never invents data.
 */
class AiToolExecutor
{
    /**
     * Tool definitions in the OpenAI-compatible function-calling format (NVIDIA NIM).
     */
    public function toolDefinitions(): array
    {
        $tools = [
            [
                'name' => 'list_projects',
                'description' => 'List all monitored projects with their id, name, category, development status, and current runtime health status. Use this to resolve a project name into an id.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'getProjectMetrics',
                'description' => 'Get metrics (requests, errors, error rate, latency) for one project over a period. Returns latest snapshot plus aggregates.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'integer', 'description' => 'Numeric project ID from list_projects. Do NOT guess — call list_projects first if you only know the project name.'],
                        'period' => ['type' => 'string', 'enum' => ['24h', '7d', '30d'], 'description' => 'Look-back window, default 24h'],
                    ],
                    'required' => ['project_id'],
                ],
            ],
            [
                'name' => 'getUptimeHistory',
                'description' => 'Get uptime history for one project: uptime percentage, incident list (critical/unreachable checks) over the last N days.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'integer', 'description' => 'Numeric project ID from list_projects. Do NOT guess — call list_projects first if you only know the project name.'],
                        'days' => ['type' => 'integer', 'description' => 'Number of days back, default 30, max 90'],
                    ],
                    'required' => ['project_id'],
                ],
            ],
            [
                'name' => 'getGithubActivity',
                'description' => 'Get GitHub activity for one project: latest commit, open issues/PRs, stars, detected tech stack.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'integer', 'description' => 'Numeric project ID from list_projects. Do NOT guess — call list_projects first if you only know the project name.'],
                    ],
                    'required' => ['project_id'],
                ],
            ],
            [
                'name' => 'getActiveAlerts',
                'description' => 'Get all currently active (unresolved) AI-generated alerts/insights across all projects, ordered by priority rank.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'compareProjects',
                'description' => 'Compare multiple projects on one metric (latency, uptime, error_rate, requests) over the last 30 days. Use this to find projects with highest latency, highest error rate, lowest uptime, or highest traffic. If project_ids is omitted or empty, all projects will be compared.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'description' => 'Optional list of project IDs. If omitted or empty, all projects will be compared.',
                        ],
                        'metric' => [
                            'type' => 'string',
                            'enum' => ['latency', 'uptime', 'error_rate', 'requests'],
                            'description' => 'Metric to compare: "latency" (for response time in ms), "uptime", "error_rate", or "requests". Default is "latency".',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'getSystemGuide',
                'description' => 'Get system guides, documentation, telemetry tracking pixel installation instructions, Core Web Vitals targets, SLA calculations, and platform features.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'topic' => [
                            'type' => 'string',
                            'enum' => ['tracking_pixel', 'sla_metrics', 'synthetic_benchmarks', 'rum_web_vitals', 'manual_overrides', 'general_features'],
                            'description' => 'Topic to fetch guide for. Default: tracking_pixel',
                        ],
                    ],
                    'required' => [],
                ],
            ],
        ];

        // Wrap in the OpenAI-compatible envelope expected by NVIDIA NIM
        return array_map(fn ($tool) => [
            'type' => 'function',
            'function' => [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'parameters' => $tool['input_schema'],
            ],
        ], $tools);
    }

    /**
     * Dispatch a tool call to its implementation.
     */
    public function execute(string $tool, array $input): mixed
    {
        return match ($tool) {
            'list_projects' => $this->listProjects(),
            'getProjectMetrics' => $this->getProjectMetrics((int) ($input['project_id'] ?? 0), $input['period'] ?? '24h'),
            'getUptimeHistory' => $this->getUptimeHistory((int) ($input['project_id'] ?? 0), (int) ($input['days'] ?? 30)),
            'getGithubActivity' => $this->getGithubActivity((int) ($input['project_id'] ?? 0)),
            'getActiveAlerts' => $this->getActiveAlerts(),
            'compareProjects' => $this->compareProjects($input['project_ids'] ?? [], $input['metric'] ?? 'latency'),
            'getSystemGuide' => $this->getSystemGuide($input['topic'] ?? 'tracking_pixel'),
            default => throw new \InvalidArgumentException("Unknown tool: {$tool}"),
        };
    }

    protected function listProjects(): array
    {
        return Project::with(['latestMetricsSnapshot'])
            ->get()
            ->map(function ($p) {
                $latest = $p->latestMetricsSnapshot;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category' => $p->category,
                    'dev_status' => $p->status,
                    'runtime_status' => $p->runtime_status,
                    'latest_latency_ms' => $latest?->avg_response_time_ms,
                    'latest_error_rate_pct' => $latest ? (float) $latest->error_rate : null,
                    'latest_http_status' => $latest?->http_status,
                ];
            })->all();
    }

    protected function getProjectMetrics(int $projectId, string $period): array
    {
        $project = Project::find($projectId);
        if (!$project) {
            return ['error' => "Project with id {$projectId} not found. Use list_projects first to see valid project IDs."];
        }

        $since = match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $snapshots = $project->metricsSnapshots()
            ->where('checked_at', '>=', $since)
            ->orderBy('checked_at')
            ->get();

        $latest = $project->latestMetricsSnapshot;

        return [
            'project' => $project->name,
            'period' => $period,
            'checks_in_period' => $snapshots->count(),
            'latest' => $latest ? [
                'at' => $latest->checked_at->toDateTimeString(),
                'status' => $latest->health_status,
                'requests' => $latest->requests_count,
                'errors' => $latest->errors_count,
                'error_rate_pct' => (float) $latest->error_rate,
                'latency_ms' => $latest->avg_response_time_ms,
                'http_status' => $latest->http_status,
            ] : null,
            'aggregates' => [
                'avg_latency_ms' => round($snapshots->whereNotNull('avg_response_time_ms')->avg('avg_response_time_ms') ?? 0),
                'max_latency_ms' => (int) $snapshots->max('avg_response_time_ms'),
                'avg_error_rate_pct' => round($snapshots->avg('error_rate') ?? 0, 2),
                'total_requests' => (int) $snapshots->sum('requests_count'),
                'total_errors' => (int) $snapshots->sum('errors_count'),
            ],
        ];
    }

    protected function getUptimeHistory(int $projectId, int $days): array
    {
        $days = max(1, min($days, 90));
        $project = Project::find($projectId);
        if (!$project) {
            return ['error' => "Project with id {$projectId} not found. Use list_projects first to see valid project IDs."];
        }

        $snapshots = $project->metricsSnapshots()
            ->where('checked_at', '>=', now()->subDays($days))
            ->orderBy('checked_at')
            ->get();

        $up = $snapshots->filter(fn ($s) => in_array($s->health_status, ['healthy', 'warning', 'maintenance']))->count();

        $incidentSnapshots = $snapshots->whereIn('health_status', ['critical', 'unreachable']);

        // Summarize per day so flapping projects don't blow up the token count
        $incidentsByDay = $incidentSnapshots
            ->groupBy(fn ($s) => $s->checked_at->toDateString())
            ->map(fn ($day, $date) => [
                'date' => $date,
                'failed_checks' => $day->count(),
                'first_at' => $day->first()->checked_at->toDateTimeString(),
                'last_at' => $day->last()->checked_at->toDateTimeString(),
                'statuses' => $day->pluck('health_status')->unique()->values()->all(),
                'sample_errors' => $day->pluck('error_message')->filter()->unique()->take(3)->values()->all(),
            ])->values();

        return [
            'project' => $project->name,
            'days' => $days,
            'total_checks' => $snapshots->count(),
            'uptime_pct' => $snapshots->isEmpty() ? null : round(($up / $snapshots->count()) * 100, 2),
            'incident_check_count' => $incidentSnapshots->count(),
            'incident_days' => $incidentsByDay->all(),
        ];
    }

    protected function getGithubActivity(int $projectId): array
    {
        $project = Project::with('latestGithubSnapshot')->find($projectId);
        if (!$project) {
            return ['error' => "Project with id {$projectId} not found. Use list_projects first to see valid project IDs."];
        }
        $gh = $project->latestGithubSnapshot;

        if (!$gh) {
            return ['project' => $project->name, 'github' => null, 'note' => 'No GitHub data synced for this project.'];
        }

        return [
            'project' => $project->name,
            'repo' => "{$project->repo_owner}/{$project->repo_name}",
            'default_branch' => $gh->default_branch,
            'last_commit' => [
                'sha' => $gh->last_commit_sha,
                'message' => $gh->last_commit_message,
                'at' => $gh->last_commit_at?->toDateTimeString(),
            ],
            'open_issues' => $gh->open_issues_count,
            'open_prs' => $gh->open_prs_count,
            'stars' => $gh->stars_count,
            'technologies' => $gh->detected_technologies,
            'synced_at' => $gh->synced_at?->toDateTimeString(),
        ];
    }

    protected function getActiveAlerts(): array
    {
        $alerts = AiInsight::with('project:id,name')
            ->active()
            ->alerts()
            ->orderByRaw('priority_rank IS NULL, priority_rank ASC')
            ->latest('generated_at')
            ->limit(30)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'project' => $i->project?->name,
                'type' => $i->type,
                'severity' => $i->severity,
                'title' => $i->title,
                'content' => $i->content,
                'priority_rank' => $i->priority_rank,
                'priority_reason' => $i->priority_reason,
                'generated_at' => $i->generated_at->toDateTimeString(),
            ])->all();

        return [
            'total_active_alerts' => count($alerts),
            'top_most_urgent' => $alerts[0] ?? null,
            'alerts' => $alerts,
        ];
    }

    protected function compareProjects(array $projectIds, string $metric): array
    {
        $metric = in_array($metric, ['latency', 'uptime', 'error_rate', 'requests']) ? $metric : 'latency';
        $filteredIds = array_filter(array_map('intval', (array) $projectIds));

        $projects = !empty($filteredIds)
            ? Project::with('latestMetricsSnapshot')->whereIn('id', array_slice($filteredIds, 0, 10))->get()
            : Project::with('latestMetricsSnapshot')->get();

        // One aggregate query for all projects instead of N per-project queries
        $aggregates = \App\Models\MetricsSnapshot::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->where('checked_at', '>=', now()->subDays(30))
            ->groupBy('project_id')
            ->selectRaw("
                project_id,
                COUNT(*) as checks,
                ROUND(AVG(avg_response_time_ms)) as avg_latency_ms,
                ROUND(AVG(error_rate), 2) as avg_error_rate,
                SUM(requests_count) as total_requests,
                SUM(CASE WHEN health_status IN ('healthy', 'warning', 'maintenance') THEN 1 ELSE 0 END) as up_checks
            ")
            ->get()
            ->keyBy('project_id');

        $results = $projects->map(function ($p) use ($metric, $aggregates) {
            $agg = $aggregates->get($p->id);
            $latest = $p->latestMetricsSnapshot;

            $value30d = match (true) {
                !$agg => null,
                $metric === 'latency' => (int) $agg->avg_latency_ms,
                $metric === 'error_rate' => (float) $agg->avg_error_rate,
                $metric === 'requests' => (int) $agg->total_requests,
                default => $agg->checks > 0 ? round($agg->up_checks / $agg->checks * 100, 2) : null,
            };

            return [
                'id' => $p->id,
                'project' => $p->name,
                'runtime_status' => $p->runtime_status,
                'latest_latency_ms' => $latest?->avg_response_time_ms,
                'latest_error_rate_pct' => $latest ? (float) $latest->error_rate : null,
                'metric_compared' => $metric,
                'value_30d' => $value30d,
            ];
        });

        // Sort so the most noteworthy project is listed first
        $sorted = $results->sortBy(function ($item) use ($metric) {
            if ($metric === 'uptime') {
                return $item['value_30d'] ?? 999; // ASC: lowest uptime first
            }
            return -($item['value_30d'] ?? 0); // DESC: highest latency/error_rate/requests first
        })->values();

        return [
            'metric' => $metric,
            'period' => '30d',
            'sorted_by' => $metric === 'uptime' ? 'lowest_first' : 'highest_first',
            'projects' => $sorted->all(),
        ];
    }

    protected function getSystemGuide(?string $topic = 'tracking_pixel'): array
    {
        $baseUrl = url('/');
        return match ($topic) {
            'tracking_pixel' => [
                'topic' => 'Tracking Pixel Integration Guide',
                'guide_markdown' => "Berikut adalah cara memasang tracking snippet pixel:\n\n1. Dapatkan Project ID dari daftar project di menu **Visitor Analytics**.\n\n2. Salin tag script 1-baris berikut:\n```html\n<script defer data-project=\"{PROJECT_ID}\" src=\"{$baseUrl}/telemetry-pixel.js\"></script>\n```\n\n3. Tempelkan (*paste*) script tag tersebut tepat sebelum penutup tag `</head>` pada website target Anda.\n\n4. Script secara otomatis akan merekam data pengunjung aktif (5 menit), total pageviews, unique visitors, resolusi layar, referrer, dan Core Web Vitals (LCP, INP, CLS, TTFB).\n\nContoh snippet siap pakai untuk project ID 7:\n```html\n<script defer data-project=\"7\" src=\"{$baseUrl}/telemetry-pixel.js\"></script>\n```",
            ],
            'sla_metrics' => [
                'topic' => 'SLA Metrics & Health Grade',
                'guide_markdown' => "SLA dihitung dari perbandingan check sukses vs total check dalam rentang 24 jam & 30 hari:\n\n- **Grade A+**: Uptime ≥ 99.9%\n- **Grade A**: Uptime ≥ 99.0%\n- **Grade B**: Uptime ≥ 95.0%\n- **Grade C**: Uptime ≥ 90.0%\n- **Grade D**: Uptime ≥ 80.0%\n- **Grade F**: Uptime < 80.0%",
            ],
            'synthetic_benchmarks' => [
                'topic' => 'Synthetic Latency Benchmarks',
                'guide_markdown' => "Synthetic Benchmarks melakukan stress-test HTTP multi-sample (5–20 konkuensi) secara terisolasi untuk mengukur persentil latency:\n\n- **P50 Latency**: Median respon time\n- **P90 Latency**: 90% sampel berada di bawah angka ini\n- **P99 Latency**: Latency puncak / worst-case spike\n- **SLA Pass Rate**: Rasio HTTP status 20x / 30x",
            ],
            'rum_web_vitals' => [
                'topic' => 'Real User Monitoring (RUM) Core Web Vitals',
                'guide_markdown' => "Metrik RUM ditangkap langsung dari browser pengunjung live:\n\n- **LCP** (Largest Contentful Paint): Target ≤ 2,500 ms\n- **INP** (Interaction to Next Paint): Target ≤ 200 ms\n- **CLS** (Cumulative Layout Shift): Target ≤ 0.10\n- **TTFB** (Time to First Byte): Target ≤ 800 ms",
            ],
            default => [
                'platform' => 'SyncOps Project Telemetry Hub & AI Diagnostics',
                'guide_markdown' => "Fitur Utama SyncOps Telemetry Hub:\n\n1. **Systems Monitor**: Monitoring uptime 30-detik otomatis.\n2. **Telemetry Logs**: Log detail riwayat HTTP check & disrupsi.\n3. **AI Ops Assistant**: Diagnostik AI real-time dengan tool calling DB.\n4. **SLA & Benchmark Suite**: Analisis SLA & Synthetic stress test.\n5. **Visitor Analytics**: RUM Core Web Vitals & Tracking Pixel telemetry.",
            ]
        };
    }
}
