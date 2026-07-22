<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$detector = app(\App\Services\TechDetectorService::class);

$owner = 'rapirawr';
$repo  = 'clean-portofolio';

echo "Testing: {$owner}/{$repo}\n\n";

$token = (string) config('services.github.token', '');
echo "Token set: " . (strlen($token) > 0 ? 'YES (' . strlen($token) . ' chars)' : 'NO') . "\n\n";

// Test Languages API directly
$client = \Illuminate\Support\Facades\Http::withHeaders([
    'Accept'     => 'application/vnd.github.v3+json',
    'User-Agent' => 'Project-Monitoring-Dashboard',
]);
if ($token) $client = $client->withToken($token);

$langResp = $client->get("https://api.github.com/repos/{$owner}/{$repo}/languages");
echo "Languages API status: " . $langResp->status() . "\n";
if ($langResp->successful()) {
    echo "Languages: " . implode(', ', array_keys($langResp->json())) . "\n";
} else {
    echo "Error: " . $langResp->body() . "\n";
}

echo "\n";

// Test root files
$rootResp = $client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/");
echo "Root files API status: " . $rootResp->status() . "\n";
if ($rootResp->successful()) {
    $files = array_column($rootResp->json(), 'name');
    echo "Root files: " . implode(', ', $files) . "\n";
} else {
    echo "Error: " . $rootResp->body() . "\n";
}

echo "\n";

// Full detect
$result = $detector->detect($owner, $repo);
echo "Detected: " . (empty($result) ? '(none)' : implode(', ', $result)) . "\n";
