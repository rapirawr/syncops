{{-- Microservice Dependency Map & Latency Bottleneck Visualizer Component --}}
<div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 200)">
    <!-- Skeleton Placeholder while loading -->
    <div x-show="loading">
        <x-skeleton variant="dependency-map" />
    </div>

    <!-- Node Map Grid -->
    <div x-show="!loading" style="display: none;" class="bg-zinc-950 border border-white/5 rounded-2xl p-5 space-y-4 font-mono shadow-2xl relative overflow-hidden">
        <!-- Ambient Background Glow -->
        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header & Toggle Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-white/5 relative z-10">
            <div class="flex items-center gap-2">
                <div class="h-6 w-6 rounded bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0-12.828a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5zm0 12.828a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-100 font-sans">Microservice Dependency Map & Latency Bottlenecks</h3>
                    <p class="text-[10px] text-zinc-500 font-sans">Live service topologies, interconnected RPC links, and latency bottleneck alerts</p>
                </div>
            </div>
        </div>

        <div class="relative z-10 py-2">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">

            <!-- Node 1: Ingestion API Gateway -->
            <div class="bg-zinc-900/40 border border-white/10 rounded-xl p-4 space-y-2 relative group hover:border-indigo-500/40 transition-all shadow-lg">
                <div class="flex items-center justify-between">
                    <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">LAYER 1 · GATEWAY</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-white font-sans">API Telemetry Ingestion</span>
                </div>
                <div class="flex items-center justify-between text-[11px] pt-1 text-zinc-400 border-t border-white/5">
                    <span>Avg Latency: <strong class="text-emerald-400">24ms</strong></span>
                    <span>Uptime: <strong class="text-white">100%</strong></span>
                </div>
            </div>

            <!-- Inter-Node Connection Arrow 1 -->
            <div class="hidden md:flex flex-col items-center justify-center relative my-auto">
                <div class="w-full h-0.5 bg-gradient-to-r from-emerald-500 to-indigo-500 relative">
                    <div class="absolute right-0 -top-1 w-2 h-2 border-t-2 border-r-2 border-indigo-400 rotate-45"></div>
                </div>
                <span class="text-[9px] text-zinc-400 bg-zinc-950 px-2 py-0.5 rounded border border-white/10 mt-1">HTTP / JSON (18ms)</span>
            </div>

            <!-- Node 2: Core Processing & Worker Pool -->
            <div class="bg-zinc-900/40 border border-white/10 rounded-xl p-4 space-y-2 relative group hover:border-indigo-500/40 transition-all shadow-lg">
                <div class="flex items-center justify-between">
                    <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">LAYER 2 · CORE QUEUE</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-white font-sans">SyncOps Worker Pool</span>
                </div>
                <div class="flex items-center justify-between text-[11px] pt-1 text-zinc-400 border-t border-white/5">
                    <span>Avg Latency: <strong class="text-emerald-400">45ms</strong></span>
                    <span>Active Queues: <strong class="text-white">4</strong></span>
                </div>
            </div>

        </div>

        <!-- Secondary Row for Monitored Target Services -->
        <div class="mt-6 pt-4 border-t border-white/5">
            <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider block mb-3 font-sans">Monitored Service Topologies & Dependency Health</span>

            @php
                $dependencyProjects = isset($projects) && $projects->count() > 0 
                    ? $projects->take(6) 
                    : \App\Models\Project::with('latestMetricsSnapshot')->take(6)->get();
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @forelse($dependencyProjects as $p)
                    @php
                        $latestSnap = $p->latestMetricsSnapshot;
                        $latency = $latestSnap ? ($latestSnap->avg_response_time_ms ?: 20) : 20;
                    @endphp
                    <div class="bg-zinc-900/30 border border-white/5 hover:border-white/15 rounded-lg p-3 flex items-center justify-between gap-3 transition">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('projects.show', $p) }}" class="text-xs font-bold text-zinc-200 hover:text-white truncate font-sans block">
                                {{ $p->name }}
                            </a>
                            <div class="text-[10px] text-zinc-500 font-mono mt-0.5 truncate">
                                {{ $p->category ?: 'Service Node' }} · {{ $latency }}ms
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-xs text-zinc-500 py-3">No monitored service nodes registered.</div>
                @endforelse
            </div>
        </div>
        </div>{{-- /.relative.z-10.py-2 --}}
    </div>{{-- /.node-map-grid (x-show=!loading) --}}
</div>{{-- /x-data wrapper --}}
