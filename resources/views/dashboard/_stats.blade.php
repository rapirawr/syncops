{{-- Fragment: top alert bar + KPI stat widgets. Rendered by DashboardController@fragmentStats --}}
@php
    $lastCheckedAt = $projects->map(fn($p) => $p->latestMetricsSnapshot?->checked_at)->filter()->max();
@endphp
<div id="top-alert-bar" class="flex items-center gap-2 px-4 py-2 bg-zinc-950 border border-white/5 rounded-lg text-xs font-mono">
    @if($stats['critical'] > 0)
        <svg class="h-3.5 w-3.5 text-status-critical-text animate-pulse flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-status-critical-text font-bold uppercase tracking-wider">{{ $stats['critical'] }} services degraded</span>
        <span class="text-zinc-600">|</span>
        <span class="text-zinc-500">Last check: {{ $lastCheckedAt ? $lastCheckedAt->diffForHumans() : 'never' }}. Action required.</span>
    @else
        <svg class="h-3.5 w-3.5 text-status-healthy-text flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-status-healthy-text font-bold uppercase tracking-wider">All systems operational</span>
        <span class="text-zinc-600">|</span>
        <span class="text-zinc-500">Last check: {{ $lastCheckedAt ? $lastCheckedAt->diffForHumans() : 'never' }}. No incidents detected.</span>
    @endif
</div>

<!-- Stats Widgets (High Density KPIs) -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6 animate-stagger">
    <!-- Total -->
    <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-4 font-mono hover-lift shimmer-card stagger-1 transition-all">
        <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Monitored Services</span>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-extrabold text-zinc-100 tracking-tight">{{ $stats['total'] }}</span>
            <span class="text-[10px] text-zinc-600">Active targets</span>
        </div>
    </div>

    <!-- Healthy -->
    <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-4 font-mono hover-lift shimmer-card stagger-2 transition-all">
        <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Healthy States</span>
        <div class="mt-2 flex items-baseline gap-2">
            <span id="widget-healthy-count" class="text-2xl font-extrabold text-status-healthy-text tracking-tight animate-status-breathe">{{ $stats['healthy'] }}</span>
            @if($stats['total'] > 0)
                <span class="text-[10px] text-zinc-500">({{ round(($stats['healthy'] / $stats['total']) * 100) }}%)</span>
            @endif
        </div>
    </div>

    <!-- Warning -->
    <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-4 font-mono hover-lift shimmer-card stagger-3 transition-all">
        <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Warnings Active</span>
        <div class="mt-2 flex items-baseline gap-2">
            <span id="widget-warning-count" class="text-2xl font-extrabold text-status-warning-text tracking-tight {{ $stats['warning'] > 0 ? 'animate-status-breathe' : '' }}">{{ $stats['warning'] }}</span>
            <span class="text-[10px] text-zinc-500">Anomalous metrics</span>
        </div>
    </div>

    <!-- Critical/Degraded -->
    <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-4 font-mono hover-lift shimmer-card stagger-4 transition-all">
        <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Offline / Degraded</span>
        <div class="mt-2 flex items-baseline gap-2">
            <span id="widget-critical-count" class="text-2xl font-extrabold text-status-critical-text tracking-tight {{ $stats['critical'] > 0 ? 'animate-pulse' : '' }}">{{ $stats['critical'] }}</span>
            <span class="text-[10px] text-zinc-500">Urgent logs</span>
        </div>
    </div>
</div>
