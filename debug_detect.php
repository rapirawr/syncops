<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$token  = (string) config('services.github.token', '');
$client = \Illuminate\Support\Facades\Http::withHeaders([
    'Accept'     => 'application/vnd.github.v3+json',
    'User-Agent' => 'Project-Monitoring-Dashboard',
])->withToken($token);

$repos = [
    ['rapirawr', 'bloxpin'],
    ['rapirawr', 'kaze-web'],
    ['rapirawr', 'convertifly'],
];

foreach ($repos as [$owner, $repo]) {
    echo "\n=== {$owner}/{$repo} ===\n";

    // Languages
    $langs = $client->get("https://api.github.com/repos/{$owner}/{$repo}/languages")->json();
    echo "Languages: " . implode(', ', array_keys($langs)) . "\n";

    // Root files
    $files = array_column($client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/")->json(), 'name');
    echo "Root files: " . implode(', ', $files) . "\n";
}
