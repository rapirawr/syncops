<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechDetectorService
{
    /**
     * Tech definitions: each entry maps a key to label, color (Tailwind bg/text), and icon SVG path.
     * color[0] = bg class, color[1] = text class
     */
    public static array $techMeta = [
        // Languages
        'php'        => ['label' => 'PHP',        'color' => ['bg-violet-950',  'text-violet-300'],  'icon' => 'php'],
        'javascript' => ['label' => 'JavaScript',  'color' => ['bg-yellow-950',  'text-yellow-300'],  'icon' => 'js'],
        'typescript' => ['label' => 'TypeScript',  'color' => ['bg-blue-950',    'text-blue-300'],    'icon' => 'ts'],
        'python'     => ['label' => 'Python',      'color' => ['bg-sky-950',     'text-sky-300'],     'icon' => 'py'],
        'go'         => ['label' => 'Go',          'color' => ['bg-cyan-950',    'text-cyan-300'],    'icon' => 'go'],
        'rust'       => ['label' => 'Rust',        'color' => ['bg-orange-950',  'text-orange-300'],  'icon' => 'rs'],
        'java'       => ['label' => 'Java',        'color' => ['bg-red-950',     'text-red-300'],     'icon' => 'java'],
        'ruby'       => ['label' => 'Ruby',        'color' => ['bg-rose-950',    'text-rose-300'],    'icon' => 'rb'],
        'csharp'     => ['label' => 'C#',          'color' => ['bg-purple-950',  'text-purple-300'],  'icon' => 'cs'],
        'css'        => ['label' => 'CSS',         'color' => ['bg-blue-950',    'text-blue-300'],    'icon' => 'css'],
        'html'       => ['label' => 'HTML',        'color' => ['bg-orange-950',  'text-orange-300'],  'icon' => 'html'],
        'shell'      => ['label' => 'Shell',       'color' => ['bg-zinc-800',    'text-zinc-300'],    'icon' => 'sh'],
        // Frameworks / Runtimes
        'laravel'    => ['label' => 'Laravel',     'color' => ['bg-red-950',     'text-red-300'],     'icon' => 'laravel'],
        'vue'        => ['label' => 'Vue',         'color' => ['bg-emerald-950', 'text-emerald-300'], 'icon' => 'vue'],
        'react'      => ['label' => 'React',       'color' => ['bg-cyan-950',    'text-cyan-300'],    'icon' => 'react'],
        'nextjs'     => ['label' => 'Next.js',     'color' => ['bg-zinc-800',    'text-zinc-300'],    'icon' => 'next'],
        'nuxtjs'     => ['label' => 'Nuxt',        'color' => ['bg-emerald-950', 'text-emerald-300'], 'icon' => 'nuxt'],
        'svelte'     => ['label' => 'Svelte',      'color' => ['bg-orange-950',  'text-orange-300'],  'icon' => 'svelte'],
        'angular'    => ['label' => 'Angular',     'color' => ['bg-red-950',     'text-red-300'],     'icon' => 'angular'],
        'express'    => ['label' => 'Express',     'color' => ['bg-zinc-800',    'text-zinc-300'],    'icon' => 'express'],
        'nestjs'     => ['label' => 'NestJS',      'color' => ['bg-red-950',     'text-red-300'],     'icon' => 'nest'],
        'django'     => ['label' => 'Django',      'color' => ['bg-green-950',   'text-green-300'],   'icon' => 'django'],
        'fastapi'    => ['label' => 'FastAPI',     'color' => ['bg-teal-950',    'text-teal-300'],    'icon' => 'fastapi'],
        'flask'      => ['label' => 'Flask',       'color' => ['bg-zinc-800',    'text-zinc-300'],    'icon' => 'flask'],
        'rails'      => ['label' => 'Rails',       'color' => ['bg-red-950',     'text-red-300'],     'icon' => 'rails'],
        'symfony'    => ['label' => 'Symfony',     'color' => ['bg-zinc-800',    'text-zinc-300'],    'icon' => 'symfony'],
        'springboot' => ['label' => 'Spring Boot', 'color' => ['bg-green-950',   'text-green-300'],   'icon' => 'spring'],
        'dotnet'     => ['label' => '.NET',        'color' => ['bg-purple-950',  'text-purple-300'],  'icon' => 'dotnet'],
        // Infrastructure / Tools
        'docker'     => ['label' => 'Docker',      'color' => ['bg-blue-950',    'text-blue-300'],    'icon' => 'docker'],
        'kubernetes' => ['label' => 'K8s',         'color' => ['bg-blue-950',    'text-blue-300'],    'icon' => 'k8s'],
        'mysql'      => ['label' => 'MySQL',       'color' => ['bg-blue-950',    'text-blue-300'],    'icon' => 'mysql'],
        'postgres'   => ['label' => 'PostgreSQL',  'color' => ['bg-sky-950',     'text-sky-300'],     'icon' => 'pg'],
        'redis'      => ['label' => 'Redis',       'color' => ['bg-red-950',     'text-red-300'],     'icon' => 'redis'],
        'mongodb'    => ['label' => 'MongoDB',     'color' => ['bg-green-950',   'text-green-300'],   'icon' => 'mongo'],
        'sqlite'     => ['label' => 'SQLite',      'color' => ['bg-blue-950',    'text-blue-300'],    'icon' => 'sqlite'],
        'graphql'    => ['label' => 'GraphQL',     'color' => ['bg-pink-950',    'text-pink-300'],    'icon' => 'gql'],
        'tailwind'   => ['label' => 'Tailwind',    'color' => ['bg-cyan-950',    'text-cyan-300'],    'icon' => 'tw'],
        'inertia'    => ['label' => 'Inertia',     'color' => ['bg-violet-950',  'text-violet-300'],  'icon' => 'inertia'],
        'livewire'   => ['label' => 'Livewire',    'color' => ['bg-pink-950',    'text-pink-300'],    'icon' => 'lw'],
        'vite'       => ['label' => 'Vite',        'color' => ['bg-yellow-950',  'text-yellow-300'],  'icon' => 'vite'],
        'webpack'    => ['label' => 'Webpack',     'color' => ['bg-blue-950',    'text-blue-300'],    'icon' => 'wp'],
        'prisma'     => ['label' => 'Prisma',      'color' => ['bg-zinc-800',    'text-zinc-300'],    'icon' => 'prisma'],
    ];

    private string $token;

    public function __construct()
    {
        $this->token = (string) config('services.github.token', '');
    }

    /**
     * Detect technologies for a GitHub repository.
     * Returns a sorted array of technology keys.
     */
    public function detect(string $owner, string $repo): array
    {
        $detected = [];

        $client = Http::withHeaders([
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'Project-Monitoring-Dashboard',
        ]);

        if ($this->token) {
            $client = $client->withToken($this->token);
        }

        $apiAvailable = true;

        // 1. GitHub Languages API
        $langResponse = $client->get("https://api.github.com/repos/{$owner}/{$repo}/languages");
        if ($langResponse->status() === 403 || $langResponse->status() === 429) {
            // Rate limited on languages — skip it but still try other endpoints
            Log::info("TechDetector: Languages API rate limited for {$owner}/{$repo}, skipping.");
        } elseif ($langResponse->successful()) {
            foreach (array_keys($langResponse->json()) as $lang) {
                $key = $this->normalizeLanguage($lang);
                if ($key) $detected[$key] = true;
            }
        }

        // 2. Root file listing + manifest parsing (single API call budget)
        $rootFiles = [];
        $filesResponse = $client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/");
        if ($filesResponse->status() === 403 || $filesResponse->status() === 429) {
            $apiAvailable = false;
            Log::info("TechDetector: Contents API rate limited for {$owner}/{$repo}, falling back to heuristics.");
        } elseif ($filesResponse->successful()) {
            $rootFiles = array_column($filesResponse->json(), 'name');
            $detected  = array_merge($detected, $this->detectFromFileList($rootFiles));
        }

        // 3. Parse package.json
        if ($apiAvailable && in_array('package.json', $rootFiles)) {
            $pkgResponse = $client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/package.json");
            if ($pkgResponse->successful()) {
                $content = $pkgResponse->json()['content'] ?? null;
                if ($content) {
                    $json = json_decode(base64_decode($content), true);
                    if ($json) $detected = array_merge($detected, $this->detectFromPackageJson($json));
                }
            }
        }

        // 4. Parse composer.json
        if ($apiAvailable && in_array('composer.json', $rootFiles)) {
            $compResponse = $client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/composer.json");
            if ($compResponse->successful()) {
                $content = $compResponse->json()['content'] ?? null;
                if ($content) {
                    $json = json_decode(base64_decode($content), true);
                    if ($json) $detected = array_merge($detected, $this->detectFromComposerJson($json));
                }
            }
        }

        // 5. Parse requirements.txt
        if ($apiAvailable && in_array('requirements.txt', $rootFiles)) {
            $reqResponse = $client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/requirements.txt");
            if ($reqResponse->successful()) {
                $content = $reqResponse->json()['content'] ?? null;
                if ($content) $detected = array_merge($detected, $this->detectFromRequirementsTxt(base64_decode($content)));
            }
        }

        // 6. Parse pyproject.toml
        if ($apiAvailable && in_array('pyproject.toml', $rootFiles)) {
            $pyResponse = $client->get("https://api.github.com/repos/{$owner}/{$repo}/contents/pyproject.toml");
            if ($pyResponse->successful()) {
                $content = $pyResponse->json()['content'] ?? null;
                if ($content) $detected = array_merge($detected, $this->detectFromPyproject(base64_decode($content)));
            }
        }

        // 7. Heuristic fallback from repo name (always runs, even without API)
        $detected = array_merge($detected, $this->detectFromRepoName($repo));

        // Return only keys that exist in our techMeta definitions
        $keys = array_keys(array_filter($detected));
        $keys = array_filter($keys, fn($k) => isset(self::$techMeta[$k]));

        return array_values($keys);
    }

    // -------------------------------------------------------------------------

    public function normalizeLang(string $lang): ?string
    {
        return $this->normalizeLanguage($lang);
    }

    private function normalizeLanguage(string $lang): ?string
    {
        return match (strtolower($lang)) {
            'php'        => 'php',
            'blade'      => 'php',   // GitHub reports Laravel projects as "Blade"
            'javascript' => 'javascript',
            'typescript' => 'typescript',
            'python'     => 'python',
            'go'         => 'go',
            'rust'       => 'rust',
            'java'       => 'java',
            'ruby'       => 'ruby',
            'c#'         => 'csharp',
            'css'        => 'css',
            'html'       => 'html',
            'shell'      => 'shell',
            default      => null,
        };
    }

    private function detectFromFileList(array $files): array
    {
        $d = [];
        $fileStr = implode(',', array_map('strtolower', $files));

        if (str_contains($fileStr, 'dockerfile') || str_contains($fileStr, 'docker-compose')) {
            $d['docker'] = true;
        }
        if (str_contains($fileStr, 'kubernetes') || str_contains($fileStr, 'k8s') || str_contains($fileStr, '.helm')) {
            $d['kubernetes'] = true;
        }
        if (str_contains($fileStr, 'vite.config')) {
            $d['vite'] = true;
        }
        if (str_contains($fileStr, 'webpack.config')) {
            $d['webpack'] = true;
        }
        if (str_contains($fileStr, 'tailwind.config')) {
            $d['tailwind'] = true;
        }
        if (str_contains($fileStr, 'next.config')) {
            $d['nextjs'] = true;
        }
        if (str_contains($fileStr, 'nuxt.config')) {
            $d['nuxtjs'] = true;
        }
        if (str_contains($fileStr, 'svelte.config')) {
            $d['svelte'] = true;
        }
        if (str_contains($fileStr, 'angular.json')) {
            $d['angular'] = true;
        }
        if (str_contains($fileStr, 'prisma') || str_contains($fileStr, 'schema.prisma')) {
            $d['prisma'] = true;
        }
        if (str_contains($fileStr, 'artisan')) {
            $d['laravel'] = true;
        }
        if (str_contains($fileStr, 'manage.py')) {
            $d['django'] = true;
        }
        if (str_contains($fileStr, 'gemfile')) {
            $d['ruby'] = true;
        }
        if (str_contains($fileStr, 'go.mod') || str_contains($fileStr, 'go.sum')) {
            $d['go'] = true;
        }
        if (str_contains($fileStr, 'cargo.toml')) {
            $d['rust'] = true;
        }
        if (str_contains($fileStr, 'pom.xml') || str_contains($fileStr, 'build.gradle')) {
            $d['java'] = true;
        }

        return $d;
    }

    private function detectFromPackageJson(array $pkg): array
    {
        $d = [];
        $allDeps = array_merge(
            $pkg['dependencies'] ?? [],
            $pkg['devDependencies'] ?? []
        );
        $depKeys = array_map('strtolower', array_keys($allDeps));

        $checks = [
            'react'          => ['react'],
            'vue'            => ['vue'],
            'nextjs'         => ['next'],
            'nuxtjs'         => ['nuxt'],
            'svelte'         => ['svelte', '@sveltejs/kit'],
            'angular'        => ['@angular/core'],
            'express'        => ['express'],
            'nestjs'         => ['@nestjs/core'],
            'tailwind'       => ['tailwindcss'],
            'vite'           => ['vite'],
            'webpack'        => ['webpack'],
            'prisma'         => ['@prisma/client', 'prisma'],
            'graphql'        => ['graphql', '@apollo/client', 'apollo-server'],
            'typescript'     => ['typescript'],
            'redis'          => ['ioredis', 'redis'],
            'mongodb'        => ['mongoose', 'mongodb'],
            'mysql'          => ['mysql2', 'mysql'],
            'postgres'       => ['pg', 'postgres'],
        ];

        foreach ($checks as $tech => $packages) {
            foreach ($packages as $pkg) {
                if (in_array($pkg, $depKeys)) {
                    $d[$tech] = true;
                    break;
                }
            }
        }

        return $d;
    }

    private function detectFromComposerJson(array $composer): array
    {
        $d = [];
        $allDeps = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );
        $depKeys = array_map('strtolower', array_keys($allDeps));

        $checks = [
            'laravel'    => ['laravel/framework'],
            'symfony'    => ['symfony/http-kernel'],
            'livewire'   => ['livewire/livewire'],
            'inertia'    => ['inertiajs/inertia-laravel'],
            'vue'        => ['inertiajs/inertia-laravel'], // inertia commonly used with vue
            'tailwind'   => [''],
            'mysql'      => [''],
            'redis'      => ['predis/predis', 'illuminate/redis'],
            'mongodb'    => ['mongodb/laravel-mongodb', 'jenssegers/mongodb'],
            'graphql'    => ['nuwave/lighthouse', 'rebing/graphql-laravel'],
        ];

        $singleChecks = [
            'laravel'    => ['laravel/framework'],
            'symfony'    => ['symfony/http-kernel', 'symfony/framework-bundle'],
            'livewire'   => ['livewire/livewire'],
            'inertia'    => ['inertiajs/inertia-laravel'],
            'redis'      => ['predis/predis'],
            'mongodb'    => ['mongodb/laravel-mongodb', 'jenssegers/mongodb'],
            'graphql'    => ['nuwave/lighthouse', 'rebing/graphql-laravel'],
        ];

        foreach ($singleChecks as $tech => $packages) {
            foreach ($packages as $pkg) {
                if (in_array($pkg, $depKeys)) {
                    $d[$tech] = true;
                    break;
                }
            }
        }

        return $d;
    }

    private function detectFromRequirementsTxt(string $text): array
    {
        $d = [];
        $text = strtolower($text);

        $checks = [
            'django'   => ['django'],
            'fastapi'  => ['fastapi'],
            'flask'    => ['flask'],
            'graphql'  => ['graphene', 'strawberry-graphql'],
            'redis'    => ['redis', 'aioredis'],
            'mongodb'  => ['pymongo', 'motor'],
            'postgres' => ['psycopg2', 'asyncpg'],
            'mysql'    => ['mysqlclient', 'aiomysql', 'pymysql'],
            'sqlite'   => [''],
        ];

        foreach ($checks as $tech => $packages) {
            foreach ($packages as $pkg) {
                if ($pkg && str_contains($text, $pkg)) {
                    $d[$tech] = true;
                    break;
                }
            }
        }

        return $d;
    }

    private function detectFromPyproject(string $text): array
    {
        return $this->detectFromRequirementsTxt($text);
    }

    /**
     * Heuristic-only detection (no API calls).
     * Uses repo name and commit message as signals.
     * Safe to call when GitHub API rate limit is exhausted.
     */
    public function detectHeuristic(string $repo, string $commitMessage = ''): array
    {
        $detected = [];
        $detected = array_merge($detected, $this->detectFromRepoName($repo));

        // Extra hints from commit message
        $msg = strtolower($commitMessage);
        $msgHints = [
            'laravel'    => ['laravel'],
            'vue'        => ['vue'],
            'react'      => ['react'],
            'nextjs'     => ['next.js', 'nextjs'],
            'tailwind'   => ['tailwind'],
            'typescript' => ['typescript', '.ts'],
            'docker'     => ['docker'],
            'prisma'     => ['prisma'],
        ];
        foreach ($msgHints as $tech => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($msg, $kw)) {
                    $detected[$tech] = true;
                    break;
                }
            }
        }

        $keys = array_keys(array_filter($detected));
        $keys = array_filter($keys, fn($k) => isset(self::$techMeta[$k]));
        return array_values($keys);
    }

    private function detectFromRepoName(string $repo): array
    {
        $d    = [];
        $name = strtolower($repo);

        $hints = [
            'laravel'    => ['laravel'],
            'vue'        => ['vue'],
            'react'      => ['react'],
            'nextjs'     => ['next', 'nextjs'],
            'nuxtjs'     => ['nuxt'],
            'svelte'     => ['svelte'],
            'angular'    => ['angular'],
            'django'     => ['django'],
            'fastapi'    => ['fastapi'],
            'flask'      => ['flask'],
            'rails'      => ['rails'],
            'express'    => ['express'],
            'nestjs'     => ['nest'],
            'springboot' => ['spring'],
            'dotnet'     => ['dotnet', '.net', 'aspnet'],
            'docker'     => ['docker'],
            'kubernetes' => ['k8s', 'kubernetes'],
        ];

        foreach ($hints as $tech => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($name, $kw)) {
                    $d[$tech] = true;
                    break;
                }
            }
        }

        return $d;
    }
}
