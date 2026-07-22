<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        view()->composer('*', function ($view) {
            try {
                if (auth()->check() && \Schema::hasTable('projects')) {
                    $projects = \App\Models\Project::with(['latestMetricsSnapshot', 'statusOverrides'])->get();
                    $degraded = $projects->filter(fn($p) => in_array($p->runtime_status, ['critical', 'unreachable']))->count();
                    $warnings = $projects->filter(fn($p) => $p->runtime_status === 'warning')->count();

                    // Top AI-prioritized alerts for the sidebar widget
                    $topAiAlerts = \Schema::hasTable('ai_insights')
                        ? \App\Models\AiInsight::with('project:id,name,slug')
                            ->active()
                            ->alerts()
                            ->orderByRaw('priority_rank IS NULL, priority_rank ASC')
                            ->latest('generated_at')
                            ->limit(3)
                            ->get()
                        : collect();

                    $view->with('globalDegradedCount', $degraded)
                         ->with('globalWarningCount', $warnings)
                         ->with('topAiAlerts', $topAiAlerts);
                } else {
                    $view->with('globalDegradedCount', 0)
                         ->with('globalWarningCount', 0)
                         ->with('topAiAlerts', collect());
                }
            } catch (\Exception $e) {
                $view->with('globalDegradedCount', 0)
                     ->with('globalWarningCount', 0)
                     ->with('topAiAlerts', collect());
            }
        });
    }
}
