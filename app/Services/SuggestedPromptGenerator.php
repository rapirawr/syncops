<?php

namespace App\Services;

use App\Models\Project;
use App\Models\AiInsight;
use Illuminate\Support\Facades\Cache;

class SuggestedPromptGenerator
{
    /**
     * Generate dynamic suggested prompts based on the current state of projects, alerts, and GitHub activity.
     * Caches the output for 60 seconds.
     *
     * @return array
     */
    public function getPrompts(): array
    {
        return Cache::remember('suggested_ai_prompts', 60, function () {
            $prompts = [];

            // Fetch all projects to inspect status, uptime, and GitHub activity
            $projects = Project::all();

            // 1. Degraded / Offline Projects
            foreach ($projects as $project) {
                $status = $project->runtime_status;
                if (in_array($status, ['critical', 'unreachable'])) {
                    $prompts[] = [
                        'text' => "Kenapa {$project->name} sedang {$status}?",
                        'priority' => 100,
                    ];
                }
            }

            // 2. Active Alerts / Warnings count
            $activeAlertsCount = AiInsight::active()->alerts()->count();
            if ($activeAlertsCount > 0) {
                $prompts[] = [
                    'text' => "Ada {$activeAlertsCount} alert aktif, mana yang paling urgent?",
                    'priority' => 90,
                ];
            }

            // 3. Low Uptime in 30 Days (< 98%)
            foreach ($projects as $project) {
                $snapshots = $project->metricsSnapshots()
                    ->where('checked_at', '>=', now()->subDays(30))
                    ->get();

                if ($snapshots->isNotEmpty()) {
                    $up = $snapshots->filter(fn ($s) => in_array($s->health_status, ['healthy', 'warning', 'maintenance']))->count();
                    $uptimePct = ($up / $snapshots->count()) * 100;
                    if ($uptimePct < 98) {
                        $prompts[] = [
                            'text' => "{$project->name} uptime-nya turun, ada apa?",
                            'priority' => 80,
                        ];
                    }
                }
            }

            // 4. Recent GitHub Commit Activity (within last 24 hours)
            foreach ($projects as $project) {
                $gh = $project->latestGithubSnapshot;
                if ($gh && $gh->last_commit_at && $gh->last_commit_at->gt(now()->subHours(24))) {
                    $prompts[] = [
                        'text' => "Apakah deploy terakhir di {$project->name} berpengaruh ke performa?",
                        'priority' => 70,
                    ];
                }
            }

            // 5. Fallback generic prompts if we don't have enough
            $fallbacks = [
                'Bandingkan uptime semua project',
                'Project mana yang paling sering error minggu ini?',
                'Bandingkan latency semua project',
                'Tampilkan ringkasan kesehatan sistem telemetry',
            ];

            foreach ($fallbacks as $index => $text) {
                $prompts[] = [
                    'text' => $text,
                    'priority' => 10 - $index,
                ];
            }

            // Sort by priority descending
            usort($prompts, fn ($a, $b) => $b['priority'] <=> $a['priority']);

            // Extract prompt text and limit to 4 suggestions
            $finalPrompts = [];
            foreach ($prompts as $p) {
                if (!in_array($p['text'], $finalPrompts)) {
                    $finalPrompts[] = $p['text'];
                }
                if (count($finalPrompts) >= 4) {
                    break;
                }
            }

            return $finalPrompts;
        });
    }
}
