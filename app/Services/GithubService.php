<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubService
{
    /**
     * Fetch repository metadata, PRs, latest commit info, and detected technologies.
     */
    public function fetchRepoData(string $owner, string $repo): ?array
    {
        $token = config('services.github.token');

        $client = Http::withHeaders([
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'Project-Monitoring-Dashboard',
        ]);

        if ($token) {
            $client = $client->withToken($token);
        }

        // 1. Fetch Repository Details
        $repoUrl      = "https://api.github.com/repos/{$owner}/{$repo}";
        $repoResponse = $client->get($repoUrl);

        if ($this->isRateLimited($repoResponse)) {
            Log::warning("GitHub API rate limit exceeded while accessing {$repoUrl}. Skipping sync.");
            return null;
        }

        if (!$repoResponse->successful()) {
            Log::error("Failed to fetch GitHub repo details for {$owner}/{$repo}: " . $repoResponse->body());
            return null;
        }

        $repoData = $repoResponse->json();

        // 2. Fetch Latest Commit Info
        $commitsUrl      = "https://api.github.com/repos/{$owner}/{$repo}/commits";
        $commitsResponse = $client->get($commitsUrl, ['per_page' => 1]);

        $commitInfo = [
            'sha'          => null,
            'message'      => null,
            'committed_at' => null,
        ];

        if ($commitsResponse->successful() && !empty($commitsResponse->json())) {
            $latestCommit = $commitsResponse->json()[0];
            $commitInfo   = [
                'sha'          => $latestCommit['sha'] ?? null,
                'message'      => $latestCommit['commit']['message'] ?? null,
                'committed_at' => isset($latestCommit['commit']['committer']['date'])
                    ? date('Y-m-d H:i:s', strtotime($latestCommit['commit']['committer']['date']))
                    : null,
            ];
        }

        // 3. Fetch Open PRs Count
        $pullsUrl      = "https://api.github.com/repos/{$owner}/{$repo}/pulls";
        $pullsResponse = $client->get($pullsUrl, ['state' => 'open', 'per_page' => 100]);
        $openPrsCount  = 0;

        if ($pullsResponse->successful()) {
            $openPrsCount = count($pullsResponse->json());
        }

        // 4. Detect technologies
        $detectedTechnologies = [];
        try {
            $techDetector         = app(TechDetectorService::class);
            $detectedTechnologies = $techDetector->detect($owner, $repo);

            // Fallback: always add primary language from repo metadata if API was rate-limited
            if (empty($detectedTechnologies)) {
                $primaryLang = $repoData['language'] ?? null;
                if ($primaryLang) {
                    $detectedTechnologies = $techDetector->detectHeuristic($repo, $primaryLang);
                    // Ensure primary language itself is included
                    $langKey = $techDetector->normalizeLang($primaryLang);
                    if ($langKey && !in_array($langKey, $detectedTechnologies)) {
                        array_unshift($detectedTechnologies, $langKey);
                    }
                }
                if (empty($detectedTechnologies)) {
                    $detectedTechnologies = $techDetector->detectHeuristic($repo, $commitInfo['message'] ?? '');
                }
            }
        } catch (\Exception $e) {
            Log::warning("Tech detection failed for {$owner}/{$repo}: " . $e->getMessage());
        }

        return [
            'default_branch'        => $repoData['default_branch'] ?? 'main',
            'stars_count'           => $repoData['stargazers_count'] ?? 0,
            'open_issues_count'     => $repoData['open_issues_count'] ?? 0,
            'open_prs_count'        => $openPrsCount,
            'last_commit_sha'       => $commitInfo['sha'],
            'last_commit_message'   => $commitInfo['message'],
            'last_commit_at'        => $commitInfo['committed_at'],
            'detected_technologies' => $detectedTechnologies,
        ];
    }

    /**
     * Fetch README + dependency manifests for AI project summarization.
     * Returns ['README.md' => content, 'composer.json' => content, ...] (truncated).
     */
    public function fetchRepoFilesForSummary(string $owner, string $repo): array
    {
        $token = config('services.github.token');

        $client = Http::withHeaders([
            'Accept'     => 'application/vnd.github.v3.raw',
            'User-Agent' => 'Project-Monitoring-Dashboard',
        ]);

        if ($token) {
            $client = $client->withToken($token);
        }

        $files = [];

        // README via dedicated endpoint (resolves README.md/readme.rst/etc.)
        $readmeResponse = $client->get("https://api.github.com/repos/{$owner}/{$repo}/readme");
        if ($readmeResponse->successful()) {
            $files['README'] = mb_substr($readmeResponse->body(), 0, 8000);
        }

        foreach (['composer.json', 'package.json'] as $manifest) {
            $response = $client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/{$manifest}");
            if ($response->successful()) {
                $files[$manifest] = mb_substr($response->body(), 0, 4000);
            }
        }

        return $files;
    }

    /**
     * Check if the API response is rate limited.
     */
    protected function isRateLimited($response): bool
    {
        if ($response->status() === 403 && str_contains($response->body(), 'rate limit')) {
            return true;
        }

        $remaining = $response->header('X-RateLimit-Remaining');
        if ($remaining !== null && (int) $remaining <= 0) {
            return true;
        }

        return false;
    }
}
