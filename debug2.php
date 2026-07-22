<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 1. Cek DB raw
echo "=== RAW DB ===\n";
$rows = DB::select("SELECT project_id, detected_technologies FROM github_snapshots");
foreach ($rows as $r) {
    echo "project_id={$r->project_id} detected_technologies={$r->detected_technologies}\n";
}

// 2. Cek repo names
echo "\n=== REPO NAMES ===\n";
foreach (\App\Models\Project::all(['id','name','repo_name']) as $p) {
    echo "id={$p->id} name={$p->name} repo_name={$p->repo_name}\n";
}

// 3. Test heuristic langsung
echo "\n=== HEURISTIC TEST ===\n";
$det = app(\App\Services\TechDetectorService::class);
$testRepos = ['clean-portofolio', 'bloxpin', 'kaze-web', 'Portofolio', 'convertifly', 'checkkhodam.github.io'];
foreach ($testRepos as $repo) {
    $techs = $det->detectHeuristic($repo, '');
    echo "$repo: " . (empty($techs) ? '(none)' : implode(', ', $techs)) . "\n";
}

// 4. Test detect() dengan repo pertama (mungkin rate limit sudah reset)
echo "\n=== FULL DETECT (rapirawr/clean-portofolio) ===\n";
$techs = $det->detect('rapirawr', 'clean-portofolio');
echo "Result: " . (empty($techs) ? '(none)' : implode(', ', $techs)) . "\n";
