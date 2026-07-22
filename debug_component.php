<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TechDetectorService;

$meta      = TechDetectorService::$techMeta;
$languages = ['php', 'javascript', 'typescript', 'python', 'go', 'rust', 'java', 'ruby', 'csharp', 'css', 'html', 'shell'];

$frameworkImpliesLang = [
    'laravel'    => ['php'],
    'symfony'    => ['php'],
    'livewire'   => ['php'],
    'inertia'    => ['php'],
    'django'     => ['python'],
    'fastapi'    => ['python'],
    'flask'      => ['python'],
    'rails'      => ['ruby'],
    'springboot' => ['java'],
    'dotnet'     => ['csharp'],
    'react'      => ['javascript'],
    'vue'        => ['javascript'],
    'nextjs'     => ['javascript', 'typescript'],
    'nuxtjs'     => ['javascript', 'typescript'],
    'svelte'     => ['javascript', 'typescript'],
    'angular'    => ['javascript', 'typescript'],
    'express'    => ['javascript'],
    'nestjs'     => ['javascript', 'typescript'],
];

$testCases = [
    'Bloxpin'    => ["php", "javascript", "html", "css", "vite", "tailwind", "laravel"],
    'Portofolio' => ["typescript", "css", "html", "docker", "vite", "react"],
    'Kaze Web'   => ["javascript", "css", "html", "vite", "react", "tailwind"],
];

foreach ($testCases as $name => $techs) {
    $allTechs = collect($techs)->filter(fn($t) => isset($meta[$t]));

    $suppressedLangs = collect([]);
    foreach ($frameworkImpliesLang as $framework => $langs) {
        if ($allTechs->contains($framework)) {
            $suppressedLangs = $suppressedLangs->merge($langs);
        }
    }

    $filtered = $allTechs->filter(function ($t) use ($suppressedLangs, $languages) {
        return in_array($t, $languages) ? !$suppressedLangs->contains($t) : true;
    });

    // Frameworks/tools first, languages last (same as component)
    $items = $filtered->sortBy(fn($t) => in_array($t, $languages) ? 1 : 0)->values();

    $limit  = 3;
    $shown  = $items->take($limit);
    $hidden = $items->skip($limit)->count();

    echo "$name:\n";
    echo "  Suppressed: " . ($suppressedLangs->isEmpty() ? '(none)' : $suppressedLangs->implode(', ')) . "\n";
    echo "  Sorted:     " . $items->implode(', ') . "\n";
    echo "  Shown(3):   " . $shown->implode(', ') . ($hidden > 0 ? " +{$hidden}" : '') . "\n\n";
}
