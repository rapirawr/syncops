@props(['techs' => [], 'limit' => null])

@php
    use App\Services\TechDetectorService;
    $meta = TechDetectorService::$techMeta;

    $languages = ['php', 'javascript', 'typescript', 'python', 'go', 'rust', 'java', 'ruby', 'csharp', 'css', 'html', 'shell'];

    // Framework -> languages it implies (suppress redundant lang badges)
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

    $allTechs = collect($techs)->filter(fn($t) => isset($meta[$t]));

    // Collect langs suppressed by present frameworks
    $suppressedLangs = collect([]);
    foreach ($frameworkImpliesLang as $framework => $langs) {
        if ($allTechs->contains($framework)) {
            $suppressedLangs = $suppressedLangs->merge($langs);
        }
    }

    // Filter out suppressed langs
    $filtered = $allTechs->filter(function ($t) use ($suppressedLangs, $languages) {
        return in_array($t, $languages) ? !$suppressedLangs->contains($t) : true;
    });

    // Sort: frameworks & tools first, languages last
    $items = $filtered->sortBy(fn($t) => in_array($t, $languages) ? 1 : 0)->values();

    $shown  = $limit ? $items->take($limit) : $items;
    $hidden = $limit ? $items->skip($limit)->count() : 0;
@endphp

@if($items->isNotEmpty())
    <div class="flex flex-wrap items-center gap-1">
        @foreach($shown as $tech)
            @php $t = $meta[$tech]; @endphp
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold font-mono tracking-wide {{ $t['color'][0] }} {{ $t['color'][1] }} border border-white/5">
                {{ $t['label'] }}
            </span>
        @endforeach
        @if($hidden > 0)
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold font-mono tracking-wide bg-zinc-800 text-zinc-400 border border-white/5">
                +{{ $hidden }}
            </span>
        @endif
    </div>
@endif
