{{-- Skeleton mirroring one project card in the grid view --}}
<div class="bg-zinc-900/30 border border-white/5 rounded-lg flex flex-col overflow-hidden">

    {{-- Top section --}}
    <div class="p-5 flex-1 flex flex-col gap-4">

        {{-- Header: category line, name, slug + status badge --}}
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="skeleton h-2 w-24 mb-2"></div>
                <div class="skeleton h-4 w-3/5"></div>
                <div class="skeleton h-2 w-2/5 mt-2"></div>
            </div>
            {{-- status badge pill --}}
            <div class="skeleton h-5 w-20 rounded flex-shrink-0"></div>
        </div>

        {{-- Metrics Grid: Requests / Errors / Latency --}}
        <div class="grid grid-cols-3 divide-x divide-white/5 rounded border border-white/5 bg-zinc-950/60 font-mono text-center">
            @for($i = 0; $i < 3; $i++)
                <div class="px-2 py-2.5 flex flex-col items-center gap-2">
                    <div class="skeleton h-1.5 w-12"></div>
                    <div class="skeleton h-3.5 w-10"></div>
                </div>
            @endfor
        </div>

        {{-- Dev stage + git row --}}
        <div class="flex items-center justify-between gap-2">
            <div class="skeleton h-4 w-16 rounded"></div>
            <div class="skeleton h-2.5 w-24"></div>
        </div>

        {{-- Tech stack badges --}}
        <div class="flex items-center gap-1.5">
            <div class="skeleton h-4.5 w-14 rounded"></div>
            <div class="skeleton h-4.5 w-16 rounded"></div>
            <div class="skeleton h-4.5 w-12 rounded"></div>
        </div>

    </div>

    {{-- Uptime History strip --}}
    <div class="px-5 py-2.5 border-t border-white/5 bg-zinc-950/20">
        <div class="flex items-center justify-between gap-2 mb-2">
            <div class="skeleton h-1.5 w-28"></div>
            <div class="skeleton h-2.5 w-8"></div>
        </div>
        <x-skeleton.uptime-bar variant="card" />
    </div>

    {{-- Footer --}}
    <div class="px-5 py-3 border-t border-white/5 bg-zinc-950/30 flex items-center justify-between gap-2">
        <div class="skeleton h-2.5 w-28"></div>
        <div class="flex items-center gap-4">
            <div class="skeleton h-3.5 w-3.5 rounded"></div>
            <div class="skeleton h-3.5 w-3.5 rounded"></div>
            <div class="skeleton h-3.5 w-3.5 rounded"></div>
        </div>
    </div>
</div>
