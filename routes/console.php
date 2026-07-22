<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('projects:check-health')->everyFiveMinutes();
Schedule::command('projects:sync-github')->hourly();

// Daily long-term AI trend analysis (latency drift, recurring incidents, capacity predictions)
Schedule::call(function () {
    if (empty(config('services.nvidia.key'))) {
        return;
    }
    \App\Models\Project::where('status', 'done')->get()->each(function ($project) {
        \App\Jobs\AnalyzeProjectHealthJob::dispatch($project, 'daily_trend');
    });
})->dailyAt('06:10')->name('ai-daily-trend-analysis');
