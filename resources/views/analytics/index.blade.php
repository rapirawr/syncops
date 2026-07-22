@extends('layouts.app')

@section('title', 'SLA & Synthetic Benchmark Analytics')

@section('content')
<div x-data="slaAnalytics()" class="space-y-6 max-w-7xl mx-auto">

    <!-- Skeleton Loading Container -->
    <div x-show="loading" class="space-y-6 font-mono">
        <div class="flex justify-between items-center pb-4 border-b border-white/5">
            <div class="space-y-2">
                <x-skeleton variant="text" width="w-56" height="h-6" />
                <x-skeleton variant="text" width="w-80" />
            </div>
            <x-skeleton variant="text" width="w-32" height="h-9" />
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-skeleton variant="card" count="4" />
        </div>
        <x-skeleton variant="chart" />
    </div>

    <!-- Main Analytics Content -->
    <div x-show="!loading" style="display: none;" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/5 pb-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-100 tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                SLA Health Scorecard & Synthetic Benchmark
            </h1>
            <p class="text-xs text-zinc-400 mt-1">
                Real-time Service Level Agreement metrics, latency percentiles (p50/p90/p99), and synthetic HTTP load testing.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Window Selection Form -->
            <form method="GET" action="{{ route('analytics.index') }}" class="flex items-center gap-2">
                <span class="text-xs text-zinc-400 font-medium">Time Window:</span>
                <x-dropdown 
                    name="window" 
                    :selected="(string)$hours" 
                    :options="[
                        '24' => 'Last 24 Hours',
                        '168' => 'Last 7 Days',
                        '720' => 'Last 30 Days'
                    ]" 
                    :submit-on-change="true"
                    align="right"
                />
            </form>

            <!-- Run All Benchmarks -->
            <button @click="startAllBenchmarks()" 
                    :disabled="allRunning"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition cursor-pointer disabled:opacity-50"
                    title="Execute HTTP benchmarks for all projects">
                <svg class="w-3.5 h-3.5" :class="allRunning ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                </svg>
                <span x-text="allRunning ? 'Benchmarking All...' : 'Run All Benchmarks'">Run All Benchmarks</span>
            </button>

            <!-- Export CSV -->
            <a href="{{ route('analytics.export', ['window' => $hours]) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 transition cursor-pointer"
               title="Export SLA Report CSV">
                <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Global Aggregates Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Average Uptime -->
        <div class="bg-zinc-900/40 border border-white/5 rounded-xl p-4 space-y-1">
            <span class="text-[10px] uppercase font-bold tracking-widest text-zinc-500">System Uptime Avg</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-bold font-mono {{ $globalUptimeAvg >= 99.0 ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ number_format($globalUptimeAvg, 2) }}%
                </span>
                <span class="text-xs text-zinc-500 font-mono">Last {{ $hours }}h</span>
            </div>
        </div>

        <!-- SLA Compliance Rate -->
        <div class="bg-zinc-900/40 border border-white/5 rounded-xl p-4 space-y-1">
            <span class="text-[10px] uppercase font-bold tracking-widest text-zinc-500">SLA Compliance Rate</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-bold font-mono text-indigo-400">
                    {{ number_format($slaComplianceRate, 1) }}%
                </span>
                <span class="text-xs text-zinc-500 font-mono">Target: 99.0%</span>
            </div>
        </div>

        <!-- System Latency Avg -->
        <div class="bg-zinc-900/40 border border-white/5 rounded-xl p-4 space-y-1">
            <span class="text-[10px] uppercase font-bold tracking-widest text-zinc-500">Average System Latency</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-bold font-mono text-zinc-100">
                    {{ $globalAvgLatency }} <span class="text-xs font-normal text-zinc-400">ms</span>
                </span>
                <span class="text-xs text-zinc-500 font-mono">Nominal</span>
            </div>
        </div>

        <!-- Monitored Projects -->
        <div class="bg-zinc-900/40 border border-white/5 rounded-xl p-4 space-y-1">
            <span class="text-[10px] uppercase font-bold tracking-widest text-zinc-500">Monitored Targets</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-bold font-mono text-zinc-100">
                    {{ $totalProjects }}
                </span>
                <span class="text-xs text-emerald-400 font-mono">Active</span>
            </div>
        </div>
    </div>

    <!-- SLA Scorecard Table -->
    <div class="bg-zinc-900/30 border border-white/5 rounded-xl overflow-hidden shadow-xl">
        <div class="px-5 py-3.5 border-b border-white/5 flex items-center justify-between bg-zinc-950/40">
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-300">Project SLA Scorecards</h2>
            <span class="text-[10px] text-zinc-500 font-mono">Click "Run Benchmark" to execute synthetic HTTP load test</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-300">
                <thead class="bg-zinc-950/60 text-[10px] uppercase text-zinc-400 border-b border-white/5 font-mono">
                    <tr>
                        <th class="py-3 px-4">Project</th>
                        <th class="py-3 px-4">SLA Grade</th>
                        <th class="py-3 px-4">Uptime %</th>
                        <th class="py-3 px-4">Avg Response</th>
                        <th class="py-3 px-4">Error Rate</th>
                        <th class="py-3 px-4">Latest Synthetic Benchmark (p50 / p90 / p99)</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($scorecards as $row)
                        @php
                            $project = $row['project'];
                            $sla = $row['sla'];
                            $bm = $row['latest_benchmark'];
                        @endphp
                        <tr class="hover:bg-white/2 transition">
                            <!-- Project Info -->
                            <td class="py-3.5 px-4">
                                <a href="{{ route('projects.show', $project) }}" class="font-bold text-zinc-100 hover:text-indigo-400 transition">
                                    {{ $project->name }}
                                </a>
                                <span class="block text-[10px] text-zinc-500 font-mono truncate max-w-[200px]">
                                    {{ $project->metrics_endpoint ?: $project->live_url ?: 'Local Metrics' }}
                                </span>
                            </td>

                            <!-- SLA Grade Badge -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border font-mono {{ $sla['bg'] ?? 'bg-emerald-500/10 border-emerald-500/30' }} {{ $sla['color'] ?? 'text-emerald-400' }}">
                                    {{ $sla['grade'] ?? 'A+' }}
                                </span>
                            </td>

                            <!-- Uptime % -->
                            <td class="py-3.5 px-4 font-mono font-semibold {{ $sla['uptime_percent'] >= 99.0 ? 'text-emerald-400' : 'text-amber-400' }}">
                                {{ number_format($sla['uptime_percent'], 2) }}%
                            </td>

                            <!-- Avg Latency -->
                            <td class="py-3.5 px-4 font-mono text-zinc-200">
                                {{ $sla['avg_latency_ms'] }} ms
                            </td>

                            <!-- Error Rate -->
                            <td class="py-3.5 px-4 font-mono {{ $sla['error_rate'] > 1.0 ? 'text-rose-400' : 'text-zinc-400' }}">
                                {{ number_format($sla['error_rate'], 2) }}%
                            </td>

                            <!-- Latest Benchmark Percentiles -->
                            <td class="py-3.5 px-4 font-mono">
                                @if($bm)
                                    <div class="flex items-center gap-2">
                                        <span class="text-emerald-400" title="p50 (Median)">p50: {{ $bm->p50_latency_ms }}ms</span>
                                        <span class="text-zinc-600">|</span>
                                        <span class="text-amber-400" title="p90">p90: {{ $bm->p90_latency_ms }}ms</span>
                                        <span class="text-zinc-600">|</span>
                                        <span class="text-indigo-400" title="p99">p99: {{ $bm->p99_latency_ms }}ms</span>
                                    </div>
                                    <span class="text-[9px] text-zinc-500 block mt-0.5">
                                        {{ $bm->executed_at->diffForHumans() }} ({{ $bm->successful_requests }}/{{ $bm->total_requests }} OK)
                                    </span>
                                @else
                                    <span class="text-zinc-500 italic text-[11px]">No benchmarks run yet</span>
                                @endif
                            </td>

                            <!-- Action Button -->
                            <td class="py-3.5 px-4 text-right">
                                <button @click="openBenchmarkModal({{ $project->id }}, '{{ addslashes($project->name) }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/20 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                    </svg>
                                    Run Benchmark
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-zinc-500 font-mono">
                                No projects registered for monitoring yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Synthetic Benchmark Runner Modal -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">

        <!-- Frosted Backdrop Blur Overlay -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-xl"
             style="-webkit-backdrop-filter: blur(20px); backdrop-filter: blur(20px);"
             @click="if(!running) showModal = false"></div>

        <!-- Frosted Glass Modal Card Container -->
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 w-full max-w-xl rounded-2xl border shadow-2xl p-6 space-y-5 backdrop-blur-2xl"
             style="background: rgba(18, 19, 24, 0.96) !important; border-color: rgba(255, 255, 255, 0.12) !important;">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-white/5 pb-3">
                <div>
                    <h3 class="text-base font-bold text-zinc-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        Synthetic Benchmark Runner
                    </h3>
                    <p class="text-xs text-zinc-400 mt-0.5" x-text="'Target: ' + activeProjectName"></p>
                </div>
                <button @click="showModal = false" :disabled="running" class="text-zinc-500 hover:text-zinc-300 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Parameters Config -->
            <div x-show="!running && !result" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1 font-mono">Total Requests</label>
                    <x-dropdown 
                        selected="20" 
                        :options="[
                            '10' => '10 Requests (Quick)',
                            '20' => '20 Requests (Standard)',
                            '50' => '50 Requests (Stress Test)'
                        ]" 
                        @change="requests = $event.detail"
                        class="w-full"
                        button-class="w-full"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1 font-mono">Concurrency</label>
                    <x-dropdown 
                        selected="5" 
                        :options="[
                            '2' => '2 Concurrent Workers',
                            '5' => '5 Concurrent Workers',
                            '10' => '10 Concurrent Workers'
                        ]" 
                        @change="concurrency = $event.detail"
                        class="w-full"
                        button-class="w-full"
                    />
                </div>
            </div>

            <!-- Execution Progress State -->
            <div x-show="running" class="py-8 flex flex-col items-center justify-center space-y-4">
                <div class="relative w-12 h-12 flex items-center justify-center">
                    <div class="animate-spin absolute w-12 h-12 rounded-full border-2 border-indigo-500 border-t-transparent"></div>
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <div class="text-center">
                    <p class="text-xs font-semibold text-zinc-200">Executing Synthetic HTTP Load Test...</p>
                    <p class="text-[10px] text-zinc-500 font-mono mt-1" x-text="`Sending ${requests} requests with ${concurrency} concurrent threads`"></p>
                </div>
            </div>

            <!-- Results View -->
            <div x-show="result" class="space-y-4 font-mono">
                <div class="p-3.5 bg-zinc-950 rounded-xl border border-white/5 space-y-3">
                    <div class="flex items-center justify-between text-xs border-b border-white/5 pb-2">
                        <span class="text-zinc-400 font-sans font-bold">Benchmark Results</span>
                        <span class="text-emerald-400 font-bold" x-text="`${result?.successful_requests}/${result?.total_requests} Requests OK`"></span>
                    </div>

                    <!-- Latency Grid -->
                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="bg-zinc-900/60 p-2 rounded border border-white/5">
                            <span class="text-[9px] text-zinc-500 block uppercase">p50 (Median)</span>
                            <span class="font-bold text-emerald-400 text-sm" x-text="`${result?.p50_latency_ms} ms`"></span>
                        </div>
                        <div class="bg-zinc-900/60 p-2 rounded border border-white/5">
                            <span class="text-[9px] text-zinc-500 block uppercase">p90</span>
                            <span class="font-bold text-amber-400 text-sm" x-text="`${result?.p90_latency_ms} ms`"></span>
                        </div>
                        <div class="bg-zinc-900/60 p-2 rounded border border-white/5">
                            <span class="text-[9px] text-zinc-500 block uppercase">p99</span>
                            <span class="font-bold text-indigo-400 text-sm" x-text="`${result?.p99_latency_ms} ms`"></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[10px] text-zinc-400 pt-1">
                        <span>Min Latency: <strong class="text-zinc-200" x-text="`${result?.min_latency_ms}ms`"></strong></span>
                        <span>Avg Latency: <strong class="text-zinc-200" x-text="`${result?.avg_latency_ms}ms`"></strong></span>
                        <span>Max Latency: <strong class="text-zinc-200" x-text="`${result?.max_latency_ms}ms`"></strong></span>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-white/5 pt-3">
                <template x-if="!running && !result">
                    <button @click="startBenchmark()" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-lg transition cursor-pointer">
                        Start Benchmark Test
                    </button>
                </template>

                <template x-if="result">
                    <button @click="result = null" 
                            class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-semibold text-xs rounded-lg transition cursor-pointer">
                        Run Again
                    </button>
                </template>

                <button @click="showModal = false" :disabled="running" 
                        class="px-3 py-1.5 bg-zinc-900 hover:bg-zinc-800 text-zinc-400 font-semibold text-xs rounded-lg border border-white/5 transition cursor-pointer">
                    Close
                </button>
            </div>

        </div>
    </div>

    </div>
</div>

<script>
window.slaAnalytics = function slaAnalytics() {
    return {
        loading: true,
        showModal: false,
        activeProjectId: null,
        activeProjectName: '',
        concurrency: '5',
        requests: '20',
        running: false,
        allRunning: false,
        result: null,

        init() {
            setTimeout(() => this.loading = false, 250);
        },

        openBenchmarkModal(projectId, projectName) {
            this.activeProjectId = projectId;
            this.activeProjectName = projectName;
            this.result = null;
            this.running = false;
            this.showModal = true;
        },

        async startBenchmark() {
            if (!this.activeProjectId || this.running) return;

            this.running = true;
            this.result = null;

            try {
                const response = await fetch(`/projects/${this.activeProjectId}/benchmark`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        concurrency: parseInt(this.concurrency),
                        requests: parseInt(this.requests)
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.result = data.benchmark;
                } else {
                    alert('Benchmark failed: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                alert('Connection error while executing benchmark.');
            } finally {
                this.running = false;
            }
        },

        async startAllBenchmarks() {
            if (this.allRunning) return;
            this.allRunning = true;

            try {
                const response = await fetch('/analytics/benchmark-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        concurrency: 5,
                        requests: 15
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: `Sukses menjalankan benchmark untuk ${data.total_tested} project!`, success: true }
                    }));
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    alert('Gagal menjalankan batch benchmark: ' + (data.message || 'Error'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi saat menjalankan batch benchmark.');
            } finally {
                this.allRunning = false;
            }
        }
    };
};

if (typeof Alpine !== 'undefined') {
    Alpine.data('slaAnalytics', window.slaAnalytics);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('slaAnalytics', window.slaAnalytics);
    });
}
</script>
@endsection
