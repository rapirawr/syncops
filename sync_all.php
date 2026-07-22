<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$projects = \App\Models\Project::whereNotNull('repo_owner')
    ->whereNotNull('repo_name')
    ->get();

foreach ($projects as $project) {
    echo "Syncing: {$project->name} ({$project->repo_owner}/{$project->repo_name})\n";
    try {
        \App\Jobs\SyncGithubDataJob::dispatchSync($project);
        $snap = $project->fresh()->latestGithubSnapshot;
        $techs = $snap?->detected_technologies ?? [];
        echo "  -> Techs: " . implode(', ', $techs) . "\n";
    } catch (\Exception $e) {
        echo "  -> ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
