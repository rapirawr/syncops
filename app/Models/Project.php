<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'repo_owner',
        'repo_name',
        'live_url',
        'status',
        'metrics_endpoint',
        'timeout_seconds',
        'order',
        'progress',
        'category',
        'layer',
        'dependencies',
        'ssl_status',
        'ssl_issuer',
        'ssl_domain',
        'ssl_valid_from',
        'ssl_valid_to',
        'ssl_days_left',
        'ssl_last_checked_at',
        'ssl_error',
    ];

    protected $casts = [
        'dependencies' => 'array',
        'ssl_valid_from' => 'datetime',
        'ssl_valid_to' => 'datetime',
        'ssl_last_checked_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $baseSlug = Str::slug($project->name) ?: ('project-' . time());
                $slug = $baseSlug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-" . $count++;
                }
                $project->slug = $slug;
            }
        });

        static::updating(function ($project) {
            if (empty($project->slug)) {
                $baseSlug = Str::slug($project->name) ?: ('project-' . time());
                $slug = $baseSlug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $project->id)->exists()) {
                    $slug = "{$baseSlug}-" . $count++;
                }
                $project->slug = $slug;
            }
        });
    }

    public function metricsSnapshots()
    {
        return $this->hasMany(MetricsSnapshot::class);
    }

    public function latestMetricsSnapshot()
    {
        return $this->hasOne(MetricsSnapshot::class)->latestOfMany('checked_at');
    }

    public function recentMetricsSnapshots()
    {
        return $this->hasMany(MetricsSnapshot::class)->latest('checked_at')->take(30);
    }

    public function getUptimePercentageAttribute()
    {
        $snapshots = $this->relationLoaded('recentMetricsSnapshots')
            ? $this->recentMetricsSnapshots
            : $this->recentMetricsSnapshots()->get();

        if ($snapshots->isEmpty()) {
            return 100;
        }

        $total = $snapshots->count();
        $upCount = $snapshots->filter(function ($snap) {
            return in_array(strtolower($snap->health_status ?? ''), ['healthy', 'warning', 'maintenance']);
        })->count();

        $percentage = ($upCount / $total) * 100;

        return $percentage == 100 ? 100 : round($percentage, 1);
    }

    public function getSlaMetrics($hours = 24)
    {
        $since = now()->subHours($hours);
        $snapshots = $this->metricsSnapshots()
            ->where('checked_at', '>=', $since)
            ->get();

        if ($snapshots->isEmpty()) {
            return [
                'uptime_percent' => 100.0,
                'avg_latency_ms' => 0,
                'error_rate' => 0,
                'grade' => 'A+',
                'bg' => 'bg-emerald-500/10 border-emerald-500/30',
                'color' => 'text-emerald-400',
            ];
        }

        $total = $snapshots->count();
        $upCount = $snapshots->filter(fn($s) => in_array(strtolower($s->health_status ?? ''), ['healthy', 'warning', 'maintenance']))->count();
        $uptimePercent = round(($upCount / $total) * 100, 2);
        $avgLatency = (int) round($snapshots->avg('avg_response_time_ms') ?? 0);
        $avgErrorRate = round((float) ($snapshots->avg('error_rate') ?? 0), 2);

        $grade = 'A+';
        if ($uptimePercent < 95.0 || $avgErrorRate > 5.0) {
            $grade = 'F';
        } elseif ($uptimePercent < 98.0 || $avgErrorRate > 2.0) {
            $grade = 'C';
        } elseif ($uptimePercent < 99.5 || $avgLatency > 500) {
            $grade = 'B';
        } elseif ($uptimePercent < 99.9 || $avgLatency > 200) {
            $grade = 'A';
        }

        $gradeStyles = match($grade) {
            'A+' => ['bg' => 'bg-emerald-500/10 border-emerald-500/30', 'color' => 'text-emerald-400'],
            'A'  => ['bg' => 'bg-emerald-500/10 border-emerald-500/20', 'color' => 'text-emerald-300'],
            'B'  => ['bg' => 'bg-indigo-500/10 border-indigo-500/30', 'color' => 'text-indigo-400'],
            'C'  => ['bg' => 'bg-amber-500/10 border-amber-500/30', 'color' => 'text-amber-400'],
            default => ['bg' => 'bg-rose-500/10 border-rose-500/30', 'color' => 'text-rose-400'],
        };

        return [
            'uptime_percent' => $uptimePercent,
            'avg_latency_ms' => $avgLatency,
            'error_rate' => $avgErrorRate,
            'grade' => $grade,
            'bg' => $gradeStyles['bg'],
            'color' => $gradeStyles['color'],
        ];
    }

    public function githubSnapshots()
    {
        return $this->hasMany(GithubSnapshot::class);
    }

    public function latestGithubSnapshot()
    {
        return $this->hasOne(GithubSnapshot::class)->latestOfMany('synced_at');
    }

    public function updates()
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    public function statusOverrides()
    {
        return $this->hasMany(ProjectStatusOverride::class)->latest();
    }

    public function activeOverride()
    {
        return $this->hasOne(ProjectStatusOverride::class)->latestOfMany();
    }

    public function aiInsights()
    {
        return $this->hasMany(AiInsight::class)->latest('generated_at');
    }

    public function latestAiInsight()
    {
        return $this->hasOne(AiInsight::class)->latestOfMany('generated_at');
    }

    public function syntheticBenchmarks()
    {
        return $this->hasMany(SyntheticBenchmark::class);
    }

    public function latestSyntheticBenchmark()
    {
        return $this->hasOne(SyntheticBenchmark::class)->latestOfMany('executed_at');
    }

    public function visitorLogs()
    {
        return $this->hasMany(VisitorLog::class);
    }

    public function getActiveVisitorsAttribute()
    {
        return $this->visitorLogs()
            ->where('created_at', '>=', now()->subMinutes(5))
            ->distinct('visitor_id')
            ->count('visitor_id');
    }

    public function getRepoLinkAttribute()
    {
        if ($this->repo_owner && $this->repo_name) {
            return "https://github.com/{$this->repo_owner}/{$this->repo_name}";
        }
        return null;
    }

    public function getRuntimeStatusAttribute()
    {
        $override = $this->activeOverride;
        if ($override && $override->isActive()) {
            return $override->forced_status ?? $override->override_status ?? 'healthy';
        }

        $snapshot = $this->latestMetricsSnapshot;
        if (!$snapshot) {
            return 'unreachable';
        }

        $status = strtolower($snapshot->health_status ?? 'unreachable');
        if (in_array($status, ['unreachable', 'critical']) || ($snapshot->http_status && $snapshot->http_status >= 400)) {
            return 'critical';
        }

        $latency = $snapshot->avg_response_time_ms;

        if ($latency === null || $latency >= 1000) {
            return 'critical';
        }

        if ($latency >= 180) {
            return 'warning';
        }

        return 'healthy';
    }

    public function getSslTargetUrl()
    {
        if (!empty($this->live_url) && str_starts_with(strtolower($this->live_url), 'https://')) {
            return $this->live_url;
        }
        if (!empty($this->metrics_endpoint) && str_starts_with(strtolower($this->metrics_endpoint), 'https://')) {
            return $this->metrics_endpoint;
        }
        return $this->live_url ?: $this->metrics_endpoint;
    }

    public function getSslBadgeAttribute()
    {
        return match ($this->ssl_status) {
            'valid' => [
                'label' => 'Valid Certificate',
                'bg' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
                'dot' => 'bg-emerald-400',
                'icon' => 'shield-check',
            ],
            'warning' => [
                'label' => "Expires in {$this->ssl_days_left} days",
                'bg' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
                'dot' => 'bg-amber-400',
                'icon' => 'shield-exclamation',
            ],
            'critical' => [
                'label' => "Expiring soon ({$this->ssl_days_left}d left)",
                'bg' => 'bg-rose-500/10 border-rose-500/30 text-rose-400 animate-pulse',
                'dot' => 'bg-rose-400',
                'icon' => 'exclamation-triangle',
            ],
            'expired' => [
                'label' => 'SSL Expired',
                'bg' => 'bg-rose-950/40 border-rose-600/50 text-rose-300',
                'dot' => 'bg-rose-500',
                'icon' => 'x-circle',
            ],
            default => [
                'label' => $this->ssl_status ? ucfirst($this->ssl_status) : 'Not Checked',
                'bg' => 'bg-slate-800/80 border-slate-700/50 text-slate-400',
                'dot' => 'bg-slate-500',
                'icon' => 'shield',
            ],
        };
    }

    /**
     * Architectural layer categorizer for Microservice Topology
     */
    public function getArchitecturalLayerAttribute(): array
    {
        $layer = $this->layer;
        $cat = strtolower($this->category ?? '');
        $name = strtolower($this->name ?? '');

        if ($layer === 1 || $layer === 2 || $layer === 3) {
            $level = (int) $layer;
        } else {
            if (in_array($cat, ['gateway', 'ingress', 'proxy', 'api_gateway', 'frontend', 'web', 'landing', 'landing_page', 'website']) || 
                str_contains($name, 'gateway') || str_contains($name, 'frontend') || str_contains($name, 'ingress') ||
                str_contains($name, 'portofolio') || str_contains($name, 'web') || str_contains($name, 'client') || 
                str_contains($name, 'landing') || str_contains($name, 'page') || str_contains($name, 'site') ||
                str_contains($name, 'profile') || str_contains($name, 'amsle') || str_contains($name, 'khodam')) {
                $level = 1;
            } elseif (in_array($cat, ['database', 'cache', 'queue', 'infrastructure', 'infra', 'storage', 'external']) || 
                str_contains($name, 'database') || str_contains($name, 'redis') || str_contains($name, 'sql') || str_contains($name, 'storage') || str_contains($name, 'db')) {
                $level = 3;
            } else {
                $level = 2;
            }
        }

        return match ($level) {
            1 => [
                'level' => 1,
                'name' => 'LAYER 1 · INGRESS & FRONTEND',
                'bg' => 'border-indigo-500/30 bg-indigo-500/5',
                'badge' => 'text-indigo-400 border-indigo-500/20 bg-indigo-500/10',
            ],
            3 => [
                'level' => 3,
                'name' => 'LAYER 3 · INFRA & DATASTORE',
                'bg' => 'border-purple-500/30 bg-purple-500/5',
                'badge' => 'text-purple-400 border-purple-500/20 bg-purple-500/10',
            ],
            default => [
                'level' => 2,
                'name' => 'LAYER 2 · CORE SERVICES',
                'bg' => 'border-emerald-500/30 bg-emerald-500/5',
                'badge' => 'text-emerald-400 border-emerald-500/20 bg-emerald-500/10',
            ],
        };
    }

    /**
     * Get average response time in ms from latest metrics snapshot
     */
    public function getAverageLatencyAttribute(): int
    {
        $snapshot = $this->relationLoaded('latestMetricsSnapshot') 
            ? $this->latestMetricsSnapshot 
            : $this->latestMetricsSnapshot()->first();

        if ($snapshot && $snapshot->avg_response_time_ms > 0) {
            return (int) $snapshot->avg_response_time_ms;
        }

        return (int) ($this->attributes['id'] ? (20 + ($this->attributes['id'] * 7) % 35) : 25);
    }

    /**
     * Check if project node is currently a Latency Bottleneck
     */
    public function getIsBottleneckAttribute(): bool
    {
        $latency = $this->average_latency;
        $status = strtolower($this->runtime_status);

        return $latency >= 180 || in_array($status, ['warning', 'critical', 'unreachable']);
    }

    /**
     * Detailed Bottleneck analysis payload
     */
    public function getBottleneckInfoAttribute(): array
    {
        $latency = $this->average_latency;
        $status = strtolower($this->runtime_status);

        if ($status === 'critical' || $status === 'unreachable') {
            return [
                'is_bottleneck' => true,
                'severity' => 'CRITICAL',
                'badge_class' => 'bg-rose-500/20 text-rose-400 border-rose-500/40 animate-pulse',
                'reason' => 'Service node unreachable or critical failure detected',
                'impact' => 'High - Downstream RPC calls timed out',
            ];
        }

        if ($status === 'warning' || $latency >= 250) {
            return [
                'is_bottleneck' => true,
                'severity' => 'HIGH',
                'badge_class' => 'bg-amber-500/20 text-amber-400 border-amber-500/40',
                'reason' => "Severe latency spike detected ({$latency}ms > 250ms threshold)",
                'impact' => 'Medium - Cascading queue backlog',
            ];
        }

        if ($latency >= 180) {
            return [
                'is_bottleneck' => true,
                'severity' => 'MODERATE',
                'badge_class' => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
                'reason' => "Elevated latency ({$latency}ms)",
                'impact' => 'Low - Approaching SLA threshold',
            ];
        }

        return [
            'is_bottleneck' => false,
            'severity' => 'NORMAL',
            'badge_class' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            'reason' => 'Optimal latency',
            'impact' => 'None',
        ];
    }
}
