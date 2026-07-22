@extends('layouts.app')

@section('title', 'Visitor Traffic & Tracking Pixel | Telemetry Hub')

@section('content')
@php
    $initialProject = $projects->firstWhere('id', $selectedProjectId) ?? $projects->first();
@endphp
<div class="space-y-6" x-data="{
    snippetModal: false,
    selectedProjectSlug: '{{ $initialProject->slug ?? '' }}',
    copied: false,
    loading: true,
    copyCode() {
        const code = `<script defer data-project=\&quot;${this.selectedProjectSlug}\&quot; src=\&quot;{{ url('/telemetry-pixel.js') }}\&quot;><\/script>`;
        navigator.clipboard.writeText(code);
        this.copied = true;
        setTimeout(() => this.copied = false, 2000);
    }
}" x-init="setTimeout(() => loading = false, 250)">

    <!-- Skeleton Loading Container -->
    <div x-show="loading" class="space-y-6 font-mono">
        <div class="flex justify-between items-center pb-4 border-b border-white/5">
            <div class="space-y-2">
                <x-skeleton variant="text" width="w-48" height="h-6" />
                <x-skeleton variant="text" width="w-72" />
            </div>
            <x-skeleton variant="text" width="w-36" height="h-9" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-skeleton variant="card" count="3" />
        </div>
        <x-skeleton variant="card" />
    </div>

    <!-- Main Visitor Content -->
    <div x-show="!loading" style="display: none;" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/5 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-white tracking-tight">Visitor Traffic Analytics</h1>
            </div>
            <p class="text-xs text-zinc-400 mt-1">Real-time visitor tracking, pageviews, and CORS-enabled snippet integration across monitored projects.</p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="snippetModal = true" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-500/20 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                Get Tracking Snippet
            </button>
        </div>
    </div>

    <!-- Filters & Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-zinc-900/60 border border-white/5 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Active Visitors (Last 5m)</p>
                <p class="text-2xl font-black text-emerald-400 mt-1 font-mono">{{ number_format($activeVisitors) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </div>
        </div>

        <div class="bg-zinc-900/60 border border-white/5 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Total Pageviews ({{ $window }})</p>
                <p class="text-2xl font-black text-white mt-1 font-mono">{{ number_format($totalPageviews) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
        </div>

        <div class="bg-zinc-900/60 border border-white/5 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Unique Visitors ({{ $window }})</p>
                <p class="text-2xl font-black text-cyan-400 mt-1 font-mono">{{ number_format($uniqueVisitors) }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Core Web Vitals (RUM) Performance Scorecard -->
    <div class="bg-zinc-950 border border-white/5 rounded-xl p-5 space-y-4 font-mono">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/5 pb-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-200 flex items-center gap-2 font-sans">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    Real User Monitoring (RUM) · Core Web Vitals
                </h3>
                <p class="text-[10px] text-zinc-400 font-sans mt-0.5">Metrics captured directly from live client browser sessions</p>
            </div>

            <!-- Device Distribution Badges -->
            <div class="flex items-center gap-2 text-[10px]">
                <span class="px-2 py-0.5 rounded bg-zinc-900 border border-white/10 text-zinc-300">
                    💻 Desktop: <strong class="text-white">{{ $deviceStats['desktop_pct'] }}%</strong>
                </span>
                <span class="px-2 py-0.5 rounded bg-zinc-900 border border-white/10 text-zinc-300">
                    📱 Mobile: <strong class="text-white">{{ $deviceStats['mobile_pct'] }}%</strong>
                </span>
                <span class="px-2 py-0.5 rounded bg-zinc-900 border border-white/10 text-zinc-300">
                    📟 Tablet: <strong class="text-white">{{ $deviceStats['tablet_pct'] }}%</strong>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- LCP Card -->
            <div class="bg-zinc-900/40 border border-white/5 rounded-lg p-3.5 space-y-1">
                <div class="flex items-center justify-between text-[10px] text-zinc-400 font-bold uppercase tracking-wider">
                    <span>LCP (Largest Paint)</span>
                    <span class="px-1.5 py-0.5 rounded text-[9px] {{ $webVitals['lcp']['grade'] === 'Good' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($webVitals['lcp']['grade'] === 'Needs Improvement' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20') }}">
                        {{ $webVitals['lcp']['grade'] }}
                    </span>
                </div>
                <div class="text-xl font-black text-white font-mono">
                    {{ $webVitals['lcp']['avg'] }} <span class="text-xs font-normal text-zinc-500">ms</span>
                </div>
                <div class="text-[9px] text-zinc-500 font-sans">Target: ≤2,500ms</div>
            </div>

            <!-- INP Card -->
            <div class="bg-zinc-900/40 border border-white/5 rounded-lg p-3.5 space-y-1">
                <div class="flex items-center justify-between text-[10px] text-zinc-400 font-bold uppercase tracking-wider">
                    <span>INP (Interaction)</span>
                    <span class="px-1.5 py-0.5 rounded text-[9px] {{ $webVitals['inp']['grade'] === 'Good' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($webVitals['inp']['grade'] === 'Needs Improvement' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20') }}">
                        {{ $webVitals['inp']['grade'] }}
                    </span>
                </div>
                <div class="text-xl font-black text-white font-mono">
                    {{ $webVitals['inp']['avg'] }} <span class="text-xs font-normal text-zinc-500">ms</span>
                </div>
                <div class="text-[9px] text-zinc-500 font-sans">Target: ≤200ms</div>
            </div>

            <!-- CLS Card -->
            <div class="bg-zinc-900/40 border border-white/5 rounded-lg p-3.5 space-y-1">
                <div class="flex items-center justify-between text-[10px] text-zinc-400 font-bold uppercase tracking-wider">
                    <span>CLS (Layout Shift)</span>
                    <span class="px-1.5 py-0.5 rounded text-[9px] {{ $webVitals['cls']['grade'] === 'Good' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($webVitals['cls']['grade'] === 'Needs Improvement' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20') }}">
                        {{ $webVitals['cls']['grade'] }}
                    </span>
                </div>
                <div class="text-xl font-black text-white font-mono">
                    {{ $webVitals['cls']['avg'] }}
                </div>
                <div class="text-[9px] text-zinc-500 font-sans">Target: ≤0.10</div>
            </div>

            <!-- TTFB Card -->
            <div class="bg-zinc-900/40 border border-white/5 rounded-lg p-3.5 space-y-1">
                <div class="flex items-center justify-between text-[10px] text-zinc-400 font-bold uppercase tracking-wider">
                    <span>TTFB (First Byte)</span>
                    <span class="px-1.5 py-0.5 rounded text-[9px] {{ $webVitals['ttfb']['grade'] === 'Good' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($webVitals['ttfb']['grade'] === 'Needs Improvement' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20') }}">
                        {{ $webVitals['ttfb']['grade'] }}
                    </span>
                </div>
                <div class="text-xl font-black text-white font-mono">
                    {{ $webVitals['ttfb']['avg'] }} <span class="text-xs font-normal text-zinc-500">ms</span>
                </div>
                <div class="text-[9px] text-zinc-500 font-sans">Target: ≤800ms</div>
            </div>
        </div>
    </div>

    <!-- Logs Table Header & Filter -->
    <div class="bg-zinc-900/60 border border-white/5 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-white/5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-white tracking-wide">Real-time Visitor Logs</h2>
            <form method="GET" action="{{ route('visitors.index') }}" class="flex items-center gap-3">
                <x-dropdown 
                    name="project_id" 
                    selected="{{ $selectedProjectId ?? '' }}" 
                    :options="['' => 'All Projects'] + $projects->pluck('name', 'id')->toArray()" 
                    :submit-on-change="true" 
                />
                <x-dropdown 
                    name="window" 
                    selected="{{ $window }}" 
                    :options="[
                        '1h' => 'Last 1 Hour',
                        '6h' => 'Last 6 Hours',
                        '24h' => 'Last 24 Hours',
                        '7d' => 'Last 7 Days',
                        '30d' => 'Last 30 Days'
                    ]" 
                    :submit-on-change="true" 
                />
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-300">
                <thead class="bg-white/[0.02] border-b border-white/5 text-[10px] uppercase font-bold text-zinc-400 font-mono">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Page Path</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Resolution</th>
                        <th class="px-4 py-3">Referrer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-mono">
                    @forelse($recentLogs as $log)
                    <tr class="hover:bg-white/[0.02] transition-colors">
                        <td class="px-4 py-3 text-zinc-400 text-[11px] whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                {{ $log->project->name ?? 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-white font-medium max-w-xs truncate" title="{{ $log->full_url }}">{{ $log->path }}</td>
                        <td class="px-4 py-3 text-zinc-400 text-[11px]">{{ $log->ip_address }}</td>
                        <td class="px-4 py-3 text-zinc-400 text-[11px]">{{ $log->screen_resolution ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-zinc-400 text-[11px] truncate max-w-xs" title="{{ $log->referrer }}">{{ $log->referrer ?? 'Direct / None' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No visitor traffic logged yet. Integrate the snippet on your website to start receiving live telemetry!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Snippet Generator Modal -->
    <div x-show="snippetModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" 
         x-cloak>
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/75" style="-webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px);" @click="snippetModal = false"></div>

        <!-- Modal Card -->
        <div class="relative z-10 w-full max-w-lg p-6 space-y-4 rounded-2xl border shadow-2xl backdrop-blur-2xl" 
             style="background: rgba(18, 19, 24, 0.98) !important; border-color: rgba(255, 255, 255, 0.12) !important;">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wider">Tracking Pixel Snippet</h3>
                <button @click="snippetModal = false" class="text-zinc-400 hover:text-white p-1 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 mb-1.5 font-mono uppercase tracking-wider">Select Monitored Target Project:</label>
                    <x-dropdown 
                        selected="{{ $initialProject->slug ?? '' }}" 
                        :options="$projects->pluck('name', 'slug')->toArray()" 
                        @select="selectedProjectSlug = $event.detail"
                        @change="selectedProjectSlug = $event.detail"
                        class="w-full"
                        button-class="w-full justify-between py-2 text-xs font-mono"
                        menu-class="w-full"
                    />
                </div>
                
                <p class="text-xs text-zinc-400 leading-relaxed font-mono">Copy and paste this 1-line script tag before the closing <code class="text-indigo-400 font-mono bg-indigo-500/10 px-1 py-0.5 rounded">&lt;/head&gt;</code> tag of your target website:</p>
                
                <div class="bg-zinc-950 border border-white/10 rounded-xl p-3.5 relative group font-mono text-xs text-indigo-300 break-all select-all shadow-inner">
                    <code>&lt;script defer data-project="<span x-text="selectedProjectSlug"></span>" src="{{ url('/telemetry-pixel.js') }}"&gt;&lt;/script&gt;</code>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
                <button @click="copyCode()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Snippet Code'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
