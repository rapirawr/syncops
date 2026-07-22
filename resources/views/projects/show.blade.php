@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'uptime',
    pageLoading: true,
    syncing: false,
    benching: false,
    pinging: false,
    pingResult: null,
    showPingModal: false,

    async runSync() {
        if (this.syncing) return;
        this.syncing = true;
        try {
            const res = await fetch('{{ route('projects.sync', $project) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Project health metrics synchronized successfully.', success: true } 
                }));
            } else if (data.skipped) {
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: data.message, success: false } 
                }));
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { message: 'Sync failed: ' + e.message, success: false } 
            }));
        } finally {
            this.syncing = false;
        }
    },

    async runBenchmark() {
        if (this.benching) return;
        this.benching = true;
        try {
            const res = await fetch('{{ route('projects.benchmark', $project) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.success) {
                const bm = data.benchmark;
                const msg = `Benchmark completed! Avg Latency: ${bm.avg_latency_ms}ms (Passed ${bm.successful_requests}/${bm.total_requests})`;
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: msg, success: true } 
                }));
            } else {
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: data.message || 'Benchmark failed', success: false } 
                }));
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { message: 'Benchmark error: ' + e.message, success: false } 
            }));
        } finally {
            this.benching = false;
        }
    },

    async runPing() {
        this.pinging = true;
        this.showPingModal = true;
        try {
            const res = await fetch('{{ route('projects.ping', $project) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            this.pingResult = await res.json();
        } catch (e) {
            this.pingResult = { success: false, message: e.message || 'Connection error' };
        } finally {
            this.pinging = false;
        }
    },

    checkingSsl: false,
    async runSslCheck() {
        if (this.checkingSsl) return;
        this.checkingSsl = true;
        try {
            const res = await fetch('{{ route('projects.check-ssl', $project) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'SSL Certificate inspected successfully.', success: true } 
                }));
                setTimeout(() => window.location.reload(), 700);
            } else {
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: data.message || 'SSL check failed.', success: false } 
                }));
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { message: 'SSL check error: ' + e.message, success: false } 
            }));
        } finally {
            this.checkingSsl = false;
        }
    }
}" x-init="setTimeout(() => pageLoading = false, 200)">

    <!-- Initial Page Loading Skeleton -->
    <div x-show="pageLoading" class="space-y-6 font-mono">
        <div class="flex items-center justify-between">
            <div class="space-y-2">
                <x-skeleton variant="text" width="w-32" />
                <x-skeleton variant="text" width="w-56" height="h-6" />
            </div>
            <div class="flex gap-2">
                <x-skeleton variant="text" width="w-20" height="h-8" />
                <x-skeleton variant="text" width="w-24" height="h-8" />
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <x-skeleton variant="card" count="5" />
        </div>
        <x-skeleton variant="chart" />
    </div>

    <!-- Main Project Content -->
    <div x-show="!pageLoading" style="display: none;" class="space-y-6">
    
    <!-- Top Action Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-zinc-950/60 border border-white/5 p-4 rounded-xl">
        <div class="flex items-center gap-3.5 flex-1 min-w-0">
            <a href="{{ route('dashboard') }}" class="p-2 rounded-lg bg-white/5 border border-white/10 text-zinc-400 hover:text-white hover:bg-white/10 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-zinc-500 font-mono tracking-wide uppercase">{{ $project->category ?: 'general' }}</span>
                    <span class="h-1 w-1 rounded-full bg-zinc-800"></span>
                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold font-mono bg-zinc-900 text-zinc-400 capitalize border border-white/5">
                        {{ str_replace('_', ' ', $project->status) }}
                    </span>
                    <!-- SLA Grade Badge -->
                    <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[9px] font-bold font-mono border {{ $slaMetrics['bg'] }} {{ $slaMetrics['color'] }}" title="24-Hour SLA Target Score">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        SLA Grade {{ $slaMetrics['grade'] }} ({{ $slaMetrics['uptime_percent'] }}%)
                    </span>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-white mt-1 flex items-center gap-2">
                    {{ $project->name }}
                    @if($project->live_url)
                        <a href="{{ $project->live_url }}" target="_blank" class="text-zinc-500 hover:text-indigo-400 transition" title="Open Live Site">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    @endif
                </h1>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 font-mono text-[10px] justify-start lg:justify-end shrink-0">
            <!-- Instant Live Ping Button -->
            <button @click="runPing()" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1.5 font-semibold text-emerald-400 hover:bg-emerald-500/20 transition cursor-pointer shadow-sm">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21" />
                </svg>
                Ping Endpoint
            </button>

            <!-- Sync Button -->
            <button type="button" @click="runSync()" :disabled="syncing" class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-zinc-950 px-3.5 py-1.5 font-semibold text-zinc-300 hover:bg-zinc-900 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shadow-sm">
                <svg class="h-3.5 w-3.5 text-zinc-500" :class="syncing ? 'animate-spin text-indigo-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span x-text="syncing ? 'Syncing...' : 'Sync'">Sync</span>
            </button>

            <!-- Benchmark Button -->
            <button type="button" @click="runBenchmark()" :disabled="benching" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-500/20 bg-indigo-500/10 px-3.5 py-1.5 font-semibold text-indigo-300 hover:bg-indigo-500/20 transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                <svg class="h-3.5 w-3.5" :class="benching ? 'animate-spin text-indigo-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
                <span x-text="benching ? 'Testing...' : 'Benchmark'">Benchmark</span>
            </button>

            <!-- Ask AI Assistant Button -->
            <a href="{{ route('ai.assistant') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-purple-500/30 bg-purple-500/10 px-3.5 py-1.5 font-semibold text-purple-300 hover:bg-purple-500/20 transition shadow-sm">
                <svg class="h-3.5 w-3.5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
                Ask AI Ops
            </a>

            <!-- Modify Button -->
            <a href="#" @click.prevent="$dispatch('open-edit-modal', { url: '{{ route('projects.edit-data', $project) }}' })" class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-zinc-950 px-3.5 py-1.5 font-semibold text-zinc-300 hover:bg-zinc-900 transition shadow-sm">
                Modify
            </a>

            <!-- Delete Button -->
            <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline" x-ref="deleteForm"
                  @submit.prevent="confirmAction({
                      title: 'Hapus Project?',
                      message: 'Project \'{{ addslashes($project->name) }}\' akan dihapus permanen beserta semua histori metrics-nya.',
                      confirmText: 'Hapus',
                      cancelText: 'Batal',
                      confirmStyle: 'danger',
                      onConfirm: () => $refs.deleteForm.submit(),
                  })">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-status-critical-border bg-status-critical-bg/20 px-3.5 py-1.5 font-semibold text-status-critical-text hover:bg-status-critical-bg/40 transition shadow-sm">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- AI Diagnostics Ops Summary Card -->
    @if($project->latestAiInsight)
        <div class="bg-purple-950/20 border border-purple-500/20 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 font-mono text-[10px]">
                        <span class="font-bold text-purple-300 uppercase tracking-widest">AI Ops Diagnostic</span>
                        <span class="text-zinc-500">•</span>
                        <span class="text-zinc-400">{{ $project->latestAiInsight->generated_at ? $project->latestAiInsight->generated_at->diffForHumans() : 'Recently' }}</span>
                    </div>
                    <h4 class="text-xs font-bold text-white mt-0.5">{{ $project->latestAiInsight->title }}</h4>
                    <p class="text-xs text-zinc-300 mt-1 font-sans leading-relaxed">{{ $project->latestAiInsight->recommendation ?: $project->latestAiInsight->content }}</p>
                </div>
            </div>
            <a href="{{ route('ai.assistant') }}" class="shrink-0 px-3 py-1.5 rounded-lg bg-purple-600/30 hover:bg-purple-600/50 border border-purple-500/30 text-purple-200 text-xs font-semibold font-mono transition">
                Consult AI Ops &rarr;
            </a>
        </div>
    @endif

    <!-- Status & Overrides Grid -->
    @php
        $resolvedStatus = $project->runtime_status;
        $latestMetric = $project->latestMetricsSnapshot;
        $gitSnapshot = $project->latestGithubSnapshot;
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- Live Status Card -->
        <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-5 flex flex-col justify-between min-h-[12rem]">
            <div>
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block font-mono">Live telemetry status</span>
                <div class="mt-4 flex items-center gap-2">
                    <x-status-badge :status="$resolvedStatus" class="text-xs" />
                </div>
            </div>
            <div class="mt-4 border-t border-white/5 pt-3.5 flex flex-col gap-1.5 text-[11px] text-zinc-400 font-mono">
                <div class="flex justify-between">
                    <span>Last checked:</span>
                    <span class="text-zinc-300">
                        {{ $latestMetric && $latestMetric->checked_at ? $latestMetric->checked_at->diffForHumans() : 'never' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Host:</span>
                    <span class="text-indigo-400 truncate max-w-[170px] hover:underline">
                        <a href="{{ $project->metrics_endpoint ?: $project->live_url }}" target="_blank">
                            {{ parse_url($project->metrics_endpoint ?: $project->live_url, PHP_URL_HOST) }}
                        </a>
                    </span>
                </div>
            </div>
        </div>

        <!-- Active Overrides Card -->
        <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-5 min-h-[12rem] flex flex-col justify-between">
            <div>
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block font-mono">Status manual overrides</span>
                @if($project->activeOverride)
                    <div class="mt-3.5 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold bg-zinc-950 text-indigo-400 border border-white/5 uppercase font-mono">
                                FORCED: {{ $project->activeOverride->forced_status }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-400 leading-normal font-sans">
                            Reason: <span class="text-zinc-200">"{{ $project->activeOverride->reason }}"</span>
                        </p>
                        @if($project->activeOverride->active_until)
                            <p class="text-[9px] text-zinc-500 font-mono">
                                EXPIRY: {{ $project->activeOverride->active_until->toDateTimeString() }} ({{ $project->activeOverride->active_until->diffForHumans() }})
                            </p>
                        @else
                            <p class="text-[9px] text-zinc-500 font-mono">EXPIRY: Indefinite</p>
                        @endif
                    </div>
                @else
                    <div class="mt-4 text-center py-4">
                        <p class="text-xs text-zinc-500 italic font-mono">No status override active.</p>
                        <p class="text-[9px] text-zinc-600 mt-1 font-mono">Uptime diagnostics rules control status.</p>
                    </div>
                @endif
            </div>
            @if($project->activeOverride)
                <div class="mt-4">
                    <form action="{{ route('projects.clear-override', $project) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-center rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs font-semibold text-zinc-400 hover:bg-zinc-900 hover:text-white transition font-mono cursor-pointer">
                            Clear Override
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Apply Override form -->
        <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-5">
            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block mb-3 font-mono">Force manual status</span>
            
            <form action="{{ route('projects.override', $project) }}" method="POST" class="space-y-3 font-mono">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[9px] font-bold text-zinc-400 uppercase">Status</label>
                        <div x-data="{
                                 open: false,
                                 status: 'healthy',
                                 statuses: {
                                     'healthy': 'Healthy',
                                     'warning': 'Warning',
                                     'critical': 'Critical',
                                     'maintenance': 'Maintenance'
                                 }
                             }"
                             @click.outside="open = false"
                             class="relative">
                            <input type="hidden" name="forced_status" id="forced_status" :value="status">
                            <button type="button" @click="open = !open"
                                    class="mt-1 flex items-center justify-between w-full rounded border border-white/10 bg-zinc-950 px-2 py-1 text-xs text-white focus:border-indigo-500 focus:outline-none transition cursor-pointer">
                                <span x-text="statuses[status] || 'Healthy'"></span>
                                <svg class="h-3 w-3 text-zinc-400 transition-transform duration-300 flex-shrink-0" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open"
                                 x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition cubic-bezier(0.16, 1, 0.3, 1) duration-150"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute left-0 right-0 mt-1.5 rounded-xl border border-white/10 bg-zinc-900/95 backdrop-blur-xl p-1 shadow-2xl z-50 overflow-hidden flex flex-col gap-0.5"
                                 style="display: none;">
                                <template x-for="(label, key) in statuses" :key="key">
                                    <button type="button" @click="status = key; open = false"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-mono transition cursor-pointer"
                                            :class="status === key ? 'bg-white/10 text-white font-semibold' : 'text-zinc-400 hover:bg-white/5 hover:text-white'">
                                        <span x-text="label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="duration_hours" class="block text-[9px] font-bold text-zinc-400 uppercase">Duration (hrs)</label>
                        <input type="number" name="duration_hours" id="duration_hours" min="1" placeholder="Indefinite"
                               class="mt-1 block w-full rounded border border-white/10 bg-zinc-950 px-2 py-1 text-xs text-white placeholder-zinc-800 focus:border-indigo-500 focus:outline-none transition font-mono">
                    </div>
                </div>
                <div>
                    <label for="reason" class="block text-[9px] font-bold text-zinc-400 uppercase">Reason *</label>
                    <input type="text" name="reason" id="reason" placeholder="e.g. Scheduled upgrade" required
                           class="mt-1 block w-full rounded border border-white/10 bg-zinc-950 px-2.5 py-1 text-xs text-white placeholder-zinc-800 focus:border-indigo-500 focus:outline-none transition font-mono">
                </div>
                <button type="submit" class="w-full rounded border border-white/10 bg-zinc-900 py-1.5 text-xs font-semibold text-white hover:bg-zinc-800 transition cursor-pointer font-mono">
                    Apply Override
                </button>
            </form>
        </div>
    </div>

    <!-- Expanded Navigation Tabs -->
    <div class="border-b border-white/5 overflow-x-auto">
        <nav class="-mb-px flex gap-6 font-mono text-xs uppercase min-w-max" aria-label="Tabs">
            <button @click="activeTab = 'uptime'" 
                    :class="activeTab === 'uptime' ? 'border-indigo-500 text-white font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer">
                Telemetry & Uptime
            </button>
            <button @click="activeTab = 'synthetic'" 
                    :class="activeTab === 'synthetic' ? 'border-indigo-500 text-white font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer">
                Synthetic Benchmarks
            </button>
            <button @click="activeTab = 'visitors'" 
                    :class="activeTab === 'visitors' ? 'border-indigo-500 text-white font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer">
                Visitor Telemetry
            </button>
            <button @click="activeTab = 'github'" 
                    :class="activeTab === 'github' ? 'border-indigo-500 text-white font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer">
                Version Control
            </button>
            <button @click="activeTab = 'history'" 
                    :class="activeTab === 'history' ? 'border-indigo-500 text-white font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer">
                Incident Logs & Updates
            </button>
            <button @click="activeTab = 'ssl'" 
                    :class="activeTab === 'ssl' ? 'border-emerald-500 text-emerald-400 font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                SSL Certificate
                @if($project->ssl_status)
                    <span class="ml-1 rounded px-1.5 py-0.2 text-[9px] font-mono border {{ $project->ssl_badge['bg'] }}">
                        {{ $project->ssl_days_left !== null ? $project->ssl_days_left . 'd' : ucfirst($project->ssl_status) }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Tab 1: Telemetry & Uptime (Chart + Details) -->
    <div x-show="activeTab === 'uptime'" class="space-y-6">
        
        <!-- Live Traffic Info Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 font-mono text-center">
            <div class="bg-zinc-900/10 border border-white/5 p-4 rounded-lg">
                <span class="block text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Requests</span>
                <span class="mt-1 block text-lg font-bold text-zinc-200">
                    {{ $latestMetric ? number_format($latestMetric->requests_count) : '—' }}
                </span>
            </div>
            <div class="bg-zinc-900/10 border border-white/5 p-4 rounded-lg">
                <span class="block text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Error Rate</span>
                <span class="mt-1 block text-lg font-bold {{ $latestMetric && $latestMetric->error_rate > 0 ? 'text-status-critical-text' : 'text-zinc-200' }}">
                    {{ $latestMetric ? number_format($latestMetric->error_rate, 2) . '%' : '—' }}
                </span>
            </div>
            <div class="bg-zinc-900/10 border border-white/5 p-4 rounded-lg">
                <span class="block text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Avg Latency</span>
                <span class="mt-1 block text-lg font-bold text-zinc-200">
                    {{ $latestMetric && $latestMetric->avg_response_time_ms ? $latestMetric->avg_response_time_ms . 'ms' : '—' }}
                </span>
            </div>
            <div class="bg-zinc-900/10 border border-white/5 p-4 rounded-lg">
                <span class="block text-[8px] font-bold text-zinc-500 uppercase tracking-widest">HTTP Status</span>
                <span class="mt-1 block text-lg font-bold text-zinc-200">
                    {{ $latestMetric && $latestMetric->http_status ? $latestMetric->http_status : '—' }}
                </span>
            </div>
            <div class="bg-zinc-900/10 border border-white/5 p-4 rounded-lg col-span-2 sm:col-span-1">
                <span class="block text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Uptime Ratio</span>
                <span class="mt-1 block text-lg font-bold {{ ($project->uptime_percentage ?? 100) >= 98 ? 'text-emerald-400' : (($project->uptime_percentage ?? 100) >= 90 ? 'text-amber-400' : 'text-rose-400') }}">
                    {{ $project->uptime_percentage ?? 100 }}%
                </span>
            </div>
        </div>

        <!-- Uptime Timeline Grid -->
        <div class="bg-zinc-900/20 border border-white/5 rounded-lg p-5 font-mono">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-3">
                <div>
                    <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Uptime Check History (Last 30 Scans)</span>
                    <span class="text-xs text-zinc-400 font-sans">Individual health statuses from automated periodic ping scans</span>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-xs font-bold text-zinc-400">30-Day Health Ratio: </span>
                    <span class="text-xs font-bold {{ ($project->uptime_percentage ?? 100) >= 98 ? 'text-emerald-400' : (($project->uptime_percentage ?? 100) >= 90 ? 'text-amber-400' : 'text-rose-400') }}">
                        {{ $project->uptime_percentage ?? 100 }}%
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-[3px] w-full">
                @php
                    $detailSnaps = $project->recentMetricsSnapshots->reverse();
                    $detailPad = max(0, 30 - $detailSnaps->count());
                @endphp
                @for($i = 0; $i < $detailPad; $i++)
                    <span class="flex-1 h-6 rounded-[2px] bg-zinc-800/60" data-tooltip="No telemetry snapshot recorded"></span>
                @endfor
                @foreach($detailSnaps as $snap)
                    @php
                        $barBg = match($snap->health_status) {
                            'healthy' => 'bg-emerald-500 hover:bg-emerald-400',
                            'warning' => 'bg-amber-500 hover:bg-amber-400',
                            'critical', 'unreachable' => 'bg-rose-500 hover:bg-rose-400',
                            'maintenance' => 'bg-sky-500 hover:bg-sky-400',
                            default => 'bg-zinc-700',
                        };
                        $tooltipStr = ($snap->checked_at ? $snap->checked_at->format('M d, H:i:s') : 'Scan') . ' • ' . strtoupper($snap->health_status) . ($snap->avg_response_time_ms ? ' (' . $snap->avg_response_time_ms . ' ms)' : '');
                    @endphp
                    <span class="flex-1 h-6 rounded-[2px] {{ $barBg }} transition-all cursor-pointer hover:scale-105" data-tooltip="{{ $tooltipStr }}"></span>
                @endforeach
            </div>
        </div>

        <!-- Historical Performance Chart -->
        <div class="bg-zinc-900/20 border border-white/5 rounded-lg p-5">
            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block mb-4 font-mono">Response metrics timeline (last {{ $metricsHistory->count() }} samples)</span>
            
            @if($metricsHistory->isEmpty())
                <div class="py-12 text-center text-xs text-zinc-500 font-mono italic">No telemetry data recorded yet. Wait for scheduler scan.</div>
            @else
                <div class="h-[260px]">
                    <canvas id="metricsChart"></canvas>
                </div>
            @endif
        </div>
    </div>

    <!-- Tab 2: Synthetic SLA & Benchmarks -->
    <div x-show="activeTab === 'synthetic'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 font-mono">
            <div class="bg-zinc-900/30 border border-white/5 p-4 rounded-xl">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">SLA 24h Grade</span>
                <span class="mt-1 text-2xl font-black {{ $slaMetrics['color'] }}">{{ $slaMetrics['grade'] }}</span>
            </div>
            <div class="bg-zinc-900/30 border border-white/5 p-4 rounded-xl">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Uptime Target</span>
                <span class="mt-1 text-2xl font-black text-white">{{ $slaMetrics['uptime_percent'] }}%</span>
            </div>
            <div class="bg-zinc-900/30 border border-white/5 p-4 rounded-xl">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Avg Latency (24h)</span>
                <span class="mt-1 text-2xl font-black text-indigo-300">{{ $slaMetrics['avg_latency_ms'] }} ms</span>
            </div>
            <div class="bg-zinc-900/30 border border-white/5 p-4 rounded-xl">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Error Rate (24h)</span>
                <span class="mt-1 text-2xl font-black {{ $slaMetrics['error_rate'] > 0 ? 'text-rose-400' : 'text-zinc-300' }}">{{ $slaMetrics['error_rate'] }}%</span>
            </div>
        </div>

        <div class="bg-zinc-900/20 border border-white/5 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between font-mono bg-zinc-950/20">
                <div>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest block">Synthetic Stress Benchmark Log</span>
                    <span class="text-[9px] text-zinc-500 font-sans">Automated multi-sample latency and SLA compliance suite results</span>
                </div>
                <button type="button" @click="runBenchmark()" :disabled="benching" class="px-3 py-1.5 rounded-lg bg-indigo-600/30 border border-indigo-500/30 text-indigo-300 hover:bg-indigo-600/50 text-xs font-semibold font-mono transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" :class="benching ? 'animate-spin text-indigo-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span x-text="benching ? 'Testing...' : 'Run New Benchmark'">Run New Benchmark</span>
                </button>
            </div>

            @if($syntheticHistory->isEmpty())
                <div class="p-8 text-center text-xs text-zinc-500 font-mono italic">
                    Belum ada histori synthetic benchmark. Klik "Run New Benchmark" untuk menjalankan stress test.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/5 text-left text-xs font-mono">
                        <thead class="bg-zinc-950/40 text-[9px] uppercase tracking-widest text-zinc-500 font-bold">
                            <tr>
                                <th class="py-3 px-6">Execution Time</th>
                                <th class="py-3 px-4 text-center">Score</th>
                                <th class="py-3 px-4 text-right">Avg Latency</th>
                                <th class="py-3 px-4 text-right">P95 Latency</th>
                                <th class="py-3 px-4 text-right">P99 Latency</th>
                                <th class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-zinc-300">
                            @foreach($syntheticHistory as $bench)
                                <tr class="hover:bg-white/2 transition">
                                    <td class="py-3.5 px-6 whitespace-nowrap text-zinc-400 text-[11px]">
                                        {{ $bench->executed_at ? $bench->executed_at->toDateTimeString() : '—' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-black">
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-zinc-950 border border-white/10 text-indigo-400">
                                            {{ $bench->score ?: 'A+' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-emerald-400 font-bold">
                                        {{ $bench->avg_latency_ms }} ms
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-zinc-300">
                                        {{ $bench->p95_latency_ms ?: round($bench->avg_latency_ms * 1.25) }} ms
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-zinc-400">
                                        {{ $bench->p99_latency_ms ?: round($bench->avg_latency_ms * 1.5) }} ms
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $bench->status === 'passed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                            {{ $bench->status ?: 'passed' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Tab 3: Visitor Telemetry & Pixel Generator -->
    <div x-show="activeTab === 'visitors'" class="space-y-6 font-mono">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-zinc-900/30 border border-white/5 p-4 rounded-xl flex items-center justify-between">
                <div>
                    <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Active Users (5m)</span>
                    <span class="text-2xl font-black text-emerald-400 mt-1 block">{{ $visitorStats['active_visitors'] }}</span>
                </div>
                <span class="px-2 py-0.5 text-[9px] font-mono font-bold tracking-wider uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded">REALTIME</span>
            </div>
            <div class="bg-zinc-900/30 border border-white/5 p-4 rounded-xl">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Total Pageviews</span>
                <span class="text-2xl font-black text-white mt-1 block">{{ number_format($visitorStats['total_views']) }}</span>
            </div>
            <div class="bg-zinc-900/30 border border-white/5 p-4 rounded-xl">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Unique Visitors</span>
                <span class="text-2xl font-black text-indigo-300 mt-1 block">{{ number_format($visitorStats['unique_visitors']) }}</span>
            </div>
        </div>

        <!-- Embed Telemetry Code Card -->
        <div class="bg-zinc-950 border border-white/10 rounded-xl p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white uppercase tracking-widest">Telemetry Embed Pixel</span>
                <span class="text-[10px] text-zinc-500">Insert before &lt;/body&gt; tag on your target website</span>
            </div>
            <div class="bg-zinc-900 border border-white/5 rounded-lg p-3 text-xs text-indigo-300 font-mono break-all select-all">
                <code>&lt;script defer data-project="{{ $project->slug }}" src="{{ url('/telemetry-pixel.js') }}"&gt;&lt;/script&gt;</code>
            </div>
        </div>

        <!-- Recent Visitor Logs Table -->
        <div class="bg-zinc-900/20 border border-white/5 rounded-xl overflow-hidden">
            <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest block px-6 py-4 border-b border-white/5 bg-zinc-950/20">Recent Visitor Traffic Logs</span>
            @if($visitorStats['recent_logs']->isEmpty())
                <div class="p-8 text-center text-xs text-zinc-500 italic">
                    Belum ada log pengunjung terrekam untuk project ini. Pasang script pixel di atas untuk mengaktifkan pelacakan pengunjung secara real-time.
                </div>
            @else
                <table class="min-w-full divide-y divide-white/5 text-left text-xs">
                    <thead class="bg-zinc-950/40 text-[9px] uppercase tracking-widest text-zinc-500 font-bold">
                        <tr>
                            <th class="py-3 px-6">Timestamp</th>
                            <th class="py-3 px-4">Visitor ID</th>
                            <th class="py-3 px-4">Page Path</th>
                            <th class="py-3 px-4">IP Address</th>
                            <th class="py-3 px-4">Browser / Device</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-zinc-300">
                        @foreach($visitorStats['recent_logs'] as $vlog)
                            <tr class="hover:bg-white/2 transition">
                                <td class="py-3.5 px-6 text-zinc-400 text-[11px] whitespace-nowrap">
                                    {{ $vlog->created_at->diffForHumans() }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-indigo-300">
                                    {{ Str::limit($vlog->visitor_id, 12) }}
                                </td>
                                <td class="py-3.5 px-4 text-white font-mono text-[11px]">
                                    {{ $vlog->path ?: '/' }}
                                </td>
                                <td class="py-3.5 px-4 text-zinc-400 font-mono">
                                    {{ $vlog->ip_address }}
                                </td>
                                <td class="py-3.5 px-4 text-zinc-500 text-[10px] truncate max-w-xs font-sans">
                                    {{ $vlog->user_agent ?: 'Unknown' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Tab 4: GitHub Repository Info -->
    <div x-show="activeTab === 'github'" class="space-y-6">
        @if($gitSnapshot)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Left metadata summary panel -->
                <div class="bg-zinc-900/20 border border-white/5 rounded-lg p-5 space-y-4 font-mono text-xs">
                    <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Git Metadata</span>
                    
                    <div class="space-y-3 text-zinc-400">
                        <div class="flex justify-between">
                            <span>Main Branch:</span>
                            <span class="font-bold text-zinc-300 bg-zinc-950 px-2 py-0.5 rounded border border-white/5">{{ $gitSnapshot->default_branch }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Stars:</span>
                            <span class="font-bold text-zinc-200">{{ $gitSnapshot->stars_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Open Issues:</span>
                            <span class="font-bold text-zinc-200">{{ $gitSnapshot->open_issues_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Open PRs:</span>
                            <span class="font-bold text-zinc-200">{{ $gitSnapshot->open_prs_count }}</span>
                        </div>
                    </div>
                    {{-- Tech Stack --}}
                    @if(is_array($gitSnapshot->detected_technologies) && count($gitSnapshot->detected_technologies) > 0)
                        <div class="pt-3 border-t border-white/5">
                            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block mb-2">Tech Stack</span>
                            <x-tech-badges :techs="$gitSnapshot->detected_technologies" />
                        </div>
                    @endif
                </div>

                <!-- Latest Commit Metadata -->
                <div class="md:col-span-2 bg-zinc-900/20 border border-white/5 rounded-lg p-5 space-y-4">
                    <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block font-mono">Latest commit bundle</span>
                    
                    @if($gitSnapshot->last_commit_sha)
                        <div class="space-y-3 font-mono text-xs">
                            <div class="flex items-center gap-2">
                                <span class="text-zinc-500">SHA Hash:</span>
                                <span class="font-bold text-zinc-300 bg-zinc-950 px-2.5 py-0.5 rounded border border-white/5">{{ $gitSnapshot->last_commit_sha }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 block">Message:</span>
                                <p class="mt-1 text-sm font-semibold text-zinc-200 leading-relaxed font-sans">
                                    "{{ $gitSnapshot->last_commit_message }}"
                                </p>
                            </div>
                            <div class="text-[9px] text-zinc-500 flex items-center justify-between">
                                <span>Synced: {{ $gitSnapshot->synced_at->diffForHumans() }}</span>
                                <span>Committed: {{ $gitSnapshot->last_commit_at ? $gitSnapshot->last_commit_at->toDateTimeString() : '—' }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500 font-mono italic">No commit history found.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-lg border border-white/5 bg-zinc-900/10 p-8 text-center text-xs text-zinc-500 font-mono">
                No version control metadata found. Verify GitHub path settings.
            </div>
        @endif
    </div>

    <!-- Tab 5: Incident Logs & Release Updates -->
    <div x-show="activeTab === 'history'" class="space-y-6">
        
        <!-- Post Release Note Form Card -->
        <div class="bg-zinc-900/30 border border-white/5 rounded-xl p-5 space-y-3">
            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest block font-mono">Post Release Note / Activity Update</span>
            <form action="{{ route('projects.updates.store', $project) }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="text" name="note" placeholder="Tuliskan catatan rilis / perubahan fitur project..." required
                       class="flex-1 rounded-lg border border-white/10 bg-zinc-950 px-3 py-2 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-sans">
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold font-mono transition cursor-pointer shrink-0">
                    Post Note
                </button>
            </form>
        </div>

        <!-- Section A: Health Degradation & Downtime Logs -->
        <div class="bg-zinc-900/20 border border-white/5 rounded-lg overflow-hidden">
            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block px-6 py-4 border-b border-white/5 font-mono bg-zinc-950/10">Recent Service Disruptions & Telemetry Incidents</span>
            @if($incidents->isEmpty())
                <div class="py-8 text-center text-xs text-zinc-500 font-mono italic">
                    <span class="text-emerald-400 font-bold font-mono">✓ No service outages or critical telemetry incidents recorded for this target.</span>
                </div>
            @else
                <table class="min-w-full divide-y divide-white/5 text-left text-xs align-middle font-mono">
                    <thead class="bg-zinc-950/50 uppercase tracking-widest text-[9px] font-bold text-zinc-500">
                        <tr>
                            <th scope="col" class="py-3 px-6">Timestamp</th>
                            <th scope="col" class="py-3 px-3">Status</th>
                            <th scope="col" class="py-3 px-3 text-center">HTTP</th>
                            <th scope="col" class="py-3 px-3 text-right">Latency</th>
                            <th scope="col" class="py-3 px-6">Error Message / Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-zinc-300">
                        @foreach($incidents as $incident)
                            <tr class="hover:bg-white/2 transition">
                                <td class="py-3.5 px-6 whitespace-nowrap text-zinc-500 text-[10px]">
                                    {{ $incident->checked_at ? $incident->checked_at->toDateTimeString() : '—' }} ({{ $incident->checked_at ? $incident->checked_at->diffForHumans() : '' }})
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                        {{ $incident->health_status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-rose-400">
                                    {{ $incident->http_status ?: '—' }}
                                </td>
                                <td class="py-3.5 px-3 text-right text-zinc-400">
                                    {{ $incident->avg_response_time_ms ? $incident->avg_response_time_ms . ' ms' : '—' }}
                                </td>
                                <td class="py-3.5 px-6 italic text-zinc-300 font-sans text-xs">
                                    {{ $incident->error_message ?: 'System scan timed out or host unreachable.' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Section B: Phase Transitions & Overrides History -->
        <div class="bg-zinc-900/20 border border-white/5 rounded-lg overflow-hidden">
            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block px-6 py-4 border-b border-white/5 font-mono bg-zinc-950/10">Development Phase Transitions & Manual Overrides Log</span>
            @if($updates->isEmpty())
                <p class="text-xs text-zinc-500 font-mono italic text-center py-6">No phase transition records logged.</p>
            @else
                <table class="min-w-full divide-y divide-white/5 text-left text-xs align-middle font-mono">
                    <thead class="bg-zinc-950/50 uppercase tracking-widest text-[9px] font-bold text-zinc-500">
                        <tr>
                            <th scope="col" class="py-3 px-6">Timestamp</th>
                            <th scope="col" class="py-3 px-3">State Change</th>
                            <th scope="col" class="py-3 px-6">Change Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-zinc-300">
                        @foreach($updates as $update)
                            <tr class="hover:bg-white/2 transition">
                                <td class="py-3.5 px-6 whitespace-nowrap text-zinc-500 text-[10px]">
                                    {{ $update->created_at->toDateTimeString() }} ({{ $update->created_at->diffForHumans() }})
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-zinc-200">
                                    <span class="text-zinc-500 capitalize">{{ $update->old_status ?: 'initial' }}</span>
                                    <span class="text-indigo-400 mx-1">&rarr;</span>
                                    <span class="text-indigo-400 font-bold capitalize">{{ $update->new_status }}</span>
                                </td>
                                <td class="py-3.5 px-6 italic text-zinc-400 leading-relaxed font-sans text-xs">
                                    {{ $update->note ?: 'No detail comment provided.' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Tab 6: SSL Certificate Expiry Monitor -->
    <div x-show="activeTab === 'ssl'" style="display: none;" class="space-y-6">
        <!-- Top Status Card -->
        <div class="bg-zinc-900/30 border border-white/5 rounded-xl p-6 relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block font-mono">TLS / SSL Certificate Health</span>
                            <h3 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                                SSL Expiry & TLS Inspector
                                @if($project->ssl_status)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-0.5 text-xs font-mono font-semibold border {{ $project->ssl_badge['bg'] }}">
                                        {{ $project->ssl_badge['label'] }}
                                    </span>
                                @endif
                            </h3>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-400 font-mono">
                        Target Host: <span class="text-indigo-400 font-semibold">{{ $project->getSslTargetUrl() ?: 'No HTTPS endpoint configured' }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="runSslCheck()" :disabled="checkingSsl" class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 font-mono text-xs font-semibold text-emerald-400 hover:bg-emerald-500/20 transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-emerald-500/5">
                        <svg class="h-4 w-4" :class="checkingSsl ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span x-text="checkingSsl ? 'Inspecting Certificate...' : 'Inspect SSL Now'">Inspect SSL Now</span>
                    </button>
                </div>
            </div>

            @if($project->ssl_days_left !== null)
                @php
                    $days = max(0, $project->ssl_days_left);
                    $pct = min(100, round(($days / 90) * 100));
                    $barColor = match(true) {
                        $days <= 7 => 'bg-rose-500 shadow-rose-500/50',
                        $days <= 30 => 'bg-amber-500 shadow-amber-500/50',
                        default => 'bg-emerald-500 shadow-emerald-500/50',
                    };
                @endphp
                <div class="mt-6 pt-5 border-t border-white/5 space-y-2">
                    <div class="flex justify-between items-center text-xs font-mono">
                        <span class="text-zinc-400">Certificate Validity Progress (Target: 90 days renewal window)</span>
                        <span class="font-bold {{ $days <= 7 ? 'text-rose-400' : ($days <= 30 ? 'text-amber-400' : 'text-emerald-400') }}">
                            {{ $project->ssl_days_left }} Days Remaining
                        </span>
                    </div>
                    <div class="w-full bg-zinc-950 rounded-full h-3 p-0.5 border border-white/10">
                        <div class="h-full rounded-full transition-all duration-500 shadow-sm {{ $barColor }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- SSL Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 font-mono">
            <div class="bg-zinc-900/20 border border-white/5 p-4 rounded-xl space-y-1">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Certificate Status</span>
                <span class="text-sm font-bold text-white capitalize flex items-center gap-1.5 mt-1">
                    {{ $project->ssl_status ?: 'Not Inspected' }}
                </span>
            </div>
            <div class="bg-zinc-900/20 border border-white/5 p-4 rounded-xl space-y-1">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Issuer / Authority</span>
                <span class="text-sm font-bold text-indigo-300 truncate block mt-1" title="{{ $project->ssl_issuer }}">
                    {{ $project->ssl_issuer ?: '—' }}
                </span>
            </div>
            <div class="bg-zinc-900/20 border border-white/5 p-4 rounded-xl space-y-1">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Common Name (CN)</span>
                <span class="text-sm font-bold text-zinc-200 truncate block mt-1" title="{{ $project->ssl_domain }}">
                    {{ $project->ssl_domain ?: '—' }}
                </span>
            </div>
            <div class="bg-zinc-900/20 border border-white/5 p-4 rounded-xl space-y-1">
                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Last Inspection</span>
                <span class="text-sm font-bold text-zinc-300 block mt-1">
                    {{ $project->ssl_last_checked_at ? $project->ssl_last_checked_at->diffForHumans() : 'Never' }}
                </span>
            </div>
        </div>

        <!-- Certificate Dates & Information Card -->
        <div class="bg-zinc-900/20 border border-white/5 rounded-xl p-5 font-mono text-xs space-y-4">
            <h4 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z" />
                </svg>
                X.509 Certificate Validity Timeline
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-zinc-950 p-3.5 rounded-lg border border-white/5 space-y-1">
                    <span class="text-[9px] text-zinc-500 block uppercase">Issued Date (Valid From)</span>
                    <span class="text-sm font-semibold text-emerald-400">
                        {{ $project->ssl_valid_from ? $project->ssl_valid_from->format('F d, Y - H:i:s T') : '—' }}
                    </span>
                </div>
                <div class="bg-zinc-950 p-3.5 rounded-lg border border-white/5 space-y-1">
                    <span class="text-[9px] text-zinc-500 block uppercase">Expiration Date (Valid Until)</span>
                    <span class="text-sm font-semibold {{ ($project->ssl_days_left ?? 99) <= 7 ? 'text-rose-400' : 'text-indigo-400' }}">
                        {{ $project->ssl_valid_to ? $project->ssl_valid_to->format('F d, Y - H:i:s T') : '—' }}
                    </span>
                </div>
            </div>

            @if($project->ssl_error)
                <div class="bg-rose-950/30 border border-rose-500/20 rounded-lg p-3.5 text-rose-300 text-xs">
                    <span class="font-bold block uppercase text-[9px] text-rose-400 mb-1">Inspection Diagnostic Error</span>
                    <p class="font-mono">{{ $project->ssl_error }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Live Ping Modal Popup -->
    <div x-show="showPingModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xl"
         style="display: none; -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px);">
        
        <div @click.outside="showPingModal = false" class="w-full max-w-lg bg-zinc-900/95 border border-white/10 rounded-2xl shadow-2xl overflow-hidden font-mono text-xs backdrop-blur-2xl">
            <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between bg-zinc-950/60">
                <div class="flex items-center gap-2.5">
                    <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold tracking-wider rounded border uppercase" 
                          :class="pinging ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : (pingResult && pingResult.success ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20')" 
                          x-text="pinging ? 'TESTING' : (pingResult && pingResult.success ? 'ONLINE' : 'OFFLINE')"></span>
                    <span class="font-bold text-white uppercase tracking-widest text-[11px]">Instant Live Ping Diagnostic</span>
                </div>
                <button @click="showPingModal = false" class="text-zinc-500 hover:text-white transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="text-[11px] text-zinc-400 flex justify-between border-b border-white/5 pb-2">
                    <span>Target URL:</span>
                    <span class="text-indigo-300 font-bold truncate max-w-[280px]">{{ $project->metrics_endpoint ?: $project->live_url }}</span>
                </div>

                <template x-if="pinging">
                    <div class="py-8 text-center space-y-3">
                        <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent"></div>
                        <p class="text-xs text-zinc-400 italic">Sending HTTP probe to endpoint...</p>
                    </div>
                </template>

                <template x-if="!pinging && pingResult">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3 text-center">
                            <div class="bg-zinc-950 p-3 rounded-lg border border-white/5">
                                <span class="text-[9px] text-zinc-500 block uppercase">HTTP Status Code</span>
                                <span class="text-xl font-black mt-0.5 block" :class="pingResult.status_code >= 200 && pingResult.status_code < 300 ? 'text-emerald-400' : 'text-rose-400'" x-text="pingResult.status_code || 'FAILED'"></span>
                            </div>
                            <div class="bg-zinc-950 p-3 rounded-lg border border-white/5">
                                <span class="text-[9px] text-zinc-500 block uppercase">Roundtrip Latency</span>
                                <span class="text-xl font-black text-indigo-400 mt-0.5 block" x-text="pingResult.latency_ms + ' ms'"></span>
                            </div>
                        </div>

                        <template x-if="pingResult.headers">
                            <div class="space-y-1.5">
                                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Response Headers</span>
                                <div class="bg-zinc-950 p-3 rounded-lg border border-white/5 text-[10px] space-y-1 overflow-x-auto text-zinc-400 max-h-36">
                                    <template x-for="(val, key) in pingResult.headers" :key="key">
                                        <div class="flex justify-between gap-2">
                                            <span class="text-indigo-300 font-semibold" x-text="key + ':'"></span>
                                            <span class="text-zinc-300 truncate" x-text="val"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="pingResult.body_snippet">
                            <div class="space-y-1.5">
                                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block">Body Payload Snippet</span>
                                <div class="bg-zinc-950 p-3 rounded-lg border border-white/5 text-[10px] text-zinc-400 font-mono break-all max-h-24 overflow-y-auto" x-text="pingResult.body_snippet"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="px-5 py-3 bg-zinc-950 border-t border-white/5 flex justify-between items-center">
                <span class="text-[10px] text-zinc-500" x-text="pingResult ? 'Checked at ' + pingResult.checked_at : ''"></span>
                <button @click="runPing()" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold cursor-pointer">
                    Re-Ping
                </button>
            </div>
        </div>
    </div>

</div>

@if(!$metricsHistory->isEmpty())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const labels = @json($metricsHistory->pluck('checked_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('H:i')));
        const latencies = @json($metricsHistory->pluck('avg_response_time_ms'));
        const errorRates = @json($metricsHistory->pluck('error_rate'));
        const ctx = document.getElementById('metricsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Latency (ms)',
                        data: latencies,
                        borderColor: '#10b981', // success emerald
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        pointRadius: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Error Rate (%)',
                        data: errorRates,
                        borderColor: '#ef4444', // critical rose
                        backgroundColor: 'transparent',
                        borderWidth: 1,
                        pointRadius: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            color: '#1a1a1e'
                        },
                        ticks: {
                            color: '#52525b',
                            font: { size: 9, family: 'JetBrains Mono' }
                        }
                    },
                    y: {
                        grid: {
                            color: '#1a1a1e'
                        },
                        ticks: {
                            color: '#a1a1aa',
                            font: { size: 9, family: 'JetBrains Mono' }
                        }
                    },
                    y1: {
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            color: '#ef4444',
                            font: { size: 9, family: 'JetBrains Mono' }
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#e4e4e7',
                            font: { size: 9, family: 'JetBrains Mono', weight: 'bold' }
                        }
                    }
                }
            }
        });
    });
</script>
@endif
</div>
@endsection
