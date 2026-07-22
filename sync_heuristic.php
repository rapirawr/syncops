<?php
/**
 * Run full tech detection for all projects and save to DB.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$detector = app(\App\Services\TechDetectorService::class);
$token    = (string) config('services.github.token', '');

echo $token ? "Token: SET\n\n" : "Token: NOT SET (rate limit applies)\n\n";

$projects = \App\Models\Project::whereNotNull('repo_owner')
    ->whereNotNull('repo_name')
    ->where('repo_name', '!=', '')
    ->with('latestGithubSnapshot')
    ->get();

foreach ($projects as $project) {
    $snap = $project->latestGithubSnapshot;
    if (!$snap) {
        echo "{$project->name}: no snapshot, skip\n";
        continue;
    }

    echo "Detecting: {$project->name} ({$project->repo_owner}/{$project->repo_name}) ... ";

    try {
        $techs = $detector->detect($project->repo_owner, $project->repo_name);
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        continue;
    }

    $snap->update(['detected_technologies' => $techs]);
    echo empty($techs) ? "(none)\n" : implode(', ', $techs) . "\n";
}

echo "\nDone.\n";
