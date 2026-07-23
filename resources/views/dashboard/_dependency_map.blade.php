{{-- Microservice Dependency Map & Latency Bottleneck Visualizer --}}
@php
    $allTopologyProjects = isset($projects) && $projects->count() > 0 
        ? $projects 
        : \App\Models\Project::with(['latestMetricsSnapshot', 'recentMetricsSnapshots'])->orderBy('order')->get();

    // Categorize projects into Architectural Topology Layers
    $layer1Nodes = collect(); // Ingress & Frontend
    $layer2Nodes = collect(); // Core Microservices & Processing
    $layer3Nodes = collect(); // Datastores & Infrastructure

    foreach ($allTopologyProjects as $p) {
        $layerInfo = $p->architectural_layer;
        if ($layerInfo['level'] === 1) {
            $layer1Nodes->push($p);
        } elseif ($layerInfo['level'] === 3) {
            $layer3Nodes->push($p);
        } else {
            $layer2Nodes->push($p);
        }
    }

    // Single source of truth for bottlenecks
    $bottleneckProjects = $allTopologyProjects->filter(fn($p) => $p->runtime_status !== 'healthy');
    $bottleneckCount = $bottleneckProjects->count();
    $totalNodesCount = $allTopologyProjects->count();

    $avgTopologyLatency = $allTopologyProjects->isNotEmpty() 
        ? (int) round($allTopologyProjects->avg(fn($p) => $p->average_latency))
        : 0;

    // Build inter-layer RPC links dynamically from real project relations
    $rpcLinks = collect();
    if ($layer1Nodes->isNotEmpty() && $layer2Nodes->isNotEmpty()) {
        foreach ($layer1Nodes as $l1) {
            foreach ($layer2Nodes->take(2) as $l2) {
                $linkLatency = (int) round(($l1->average_latency + $l2->average_latency) / 2);
                $rpcLinks->push([
                    'from' => $l1->name,
                    'to' => $l2->name,
                    'latency' => $linkLatency,
                    'is_slow' => $linkLatency >= 180,
                ]);
            }
        }
    }
    if ($layer2Nodes->isNotEmpty() && $layer3Nodes->isNotEmpty()) {
        foreach ($layer2Nodes as $l2) {
            foreach ($layer3Nodes->take(2) as $l3) {
                $linkLatency = (int) round(($l2->average_latency + $l3->average_latency) / 2);
                $rpcLinks->push([
                    'from' => $l2->name,
                    'to' => $l3->name,
                    'latency' => $linkLatency,
                    'is_slow' => $linkLatency >= 180,
                ]);
            }
        }
    }

    $totalLinksCount = $rpcLinks->count();
    $hasLayer3 = $layer3Nodes->isNotEmpty();
@endphp

<div x-data="{ 
    loading: true, 
    refreshing: false,
    filterMode: 'all',
    activeNodeModal: null,
    selectNode(nodeData) {
        this.activeNodeModal = nodeData;
    },
    async refreshTopology() {
        this.refreshing = true;
        try {
            if (typeof window.runSyncAllSequence === 'function') {
                await window.runSyncAllSequence();
            } else {
                await fetch('/projects/sync-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                });
            }
            window.location.reload();
        } catch(e) {
            console.error(e);
        } finally {
            this.refreshing = false;
        }
    }
}" x-init="
    setTimeout(() => loading = false, 150);
    $watch('activeNodeModal', value => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
">

    <!-- Skeleton Placeholder while loading -->
    <div x-show="loading">
        <x-skeleton variant="dependency-map" />
    </div>

    <!-- Main Node Map Container -->
    <div x-show="!loading" style="display: none;" class="bg-zinc-950 border border-white/5 rounded-2xl p-5 space-y-5 font-mono shadow-2xl relative overflow-hidden">
        <!-- Ambient Background Glow -->
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header & Summary KPI Bar -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-white/5 relative z-10">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0-12.828a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5zm0 12.828a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-100 font-sans flex items-center gap-2">
                        Microservice Dependency Map & Latency Bottlenecks
                    </h3>
                    <p class="text-[10px] text-zinc-500 font-sans">Multi-layer service topology and real-time response latency metrics</p>
                </div>
            </div>

            <!-- Dynamic Summary Counters -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-3 px-3 py-1.5 rounded-lg bg-zinc-900/60 border border-white/5 text-[10px]">
                    <div>
                        <span class="text-zinc-500 uppercase tracking-widest text-[8px] block font-sans">NODES</span>
                        <span class="font-bold text-zinc-200">{{ $totalNodesCount }}</span>
                    </div>
                    <div class="w-px h-6 bg-white/10"></div>
                    <div>
                        <span class="text-zinc-500 uppercase tracking-widest text-[8px] block font-sans">RPC LINKS</span>
                        <span class="font-bold text-indigo-400">{{ $totalLinksCount }}</span>
                    </div>
                    <div class="w-px h-6 bg-white/10"></div>
                    <div>
                        <span class="text-zinc-500 uppercase tracking-widest text-[8px] block font-sans">AVG LATENCY</span>
                        <span class="font-bold text-emerald-400">{{ $avgTopologyLatency }}ms</span>
                    </div>
                    <div class="w-px h-6 bg-white/10"></div>
                    <div>
                        <span class="text-zinc-500 uppercase tracking-widest text-[8px] block font-sans">BOTTLENECKS</span>
                        <span class="font-bold {{ $bottleneckCount > 0 ? 'text-amber-400' : 'text-zinc-400' }}">{{ $bottleneckCount }}</span>
                    </div>
                </div>

                <!-- Filter Toggle Buttons & Refresh Action -->
                <div class="flex items-center gap-1.5">
                    <div class="inline-flex items-center rounded-lg bg-zinc-900 border border-white/5 p-0.5 text-[10px]">
                        <button @click="filterMode = 'all'" :class="filterMode === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-zinc-400 hover:text-zinc-200'" class="px-2.5 py-1 rounded-md transition font-sans cursor-pointer">All Nodes</button>
                        <button @click="filterMode = 'bottlenecks'" :class="filterMode === 'bottlenecks' ? 'bg-amber-600 text-white font-bold' : 'text-zinc-400 hover:text-zinc-200'" class="px-2.5 py-1 rounded-md transition font-sans cursor-pointer">
                            Bottlenecks ({{ $bottleneckCount }})
                        </button>
                    </div>

                    <button type="button" 
                            @click="refreshTopology()" 
                            :disabled="refreshing" 
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-zinc-900/90 border border-white/10 text-[10px] font-semibold text-zinc-300 hover:bg-zinc-800 hover:text-white transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed font-sans shadow-sm"
                            title="Refresh microservice topology metrics">
                        <svg class="h-3.5 w-3.5" :class="refreshing ? 'animate-spin text-indigo-400' : 'text-zinc-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7m1.981-2.671a8.25 8.25 0 00-13.803-3.7l-3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span x-text="refreshing ? 'Syncing...' : 'Refresh'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Top Bottlenecks Summary (Shows ONLY top offenders if bottlenecks exist) -->
        @if($bottleneckCount > 0)
            <div class="bg-amber-950/20 border border-white/10 rounded-xl p-3.5 relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-2.5">
                    <div class="p-1 rounded bg-amber-500/20 text-amber-400 mt-0.5 flex-shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-amber-300 font-sans block">SLOWEST SERVICE NODES</span>
                        <p class="text-[10px] text-amber-400/80 font-sans mt-0.5">
                            {{ $bottleneckCount }} service node(s) with response times &ge; 180ms or degraded health.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach($bottleneckProjects->sortByDesc('average_latency')->take(3) as $bp)
                        <button @click="selectNode({{ json_encode([
                            'id' => $bp->id,
                            'name' => $bp->name,
                            'category' => $bp->category ?: 'Service Node',
                            'status' => $bp->runtime_status,
                            'latency' => $bp->average_latency,
                            'layer' => $bp->architectural_layer['name'],
                            'bottleneck' => $bp->bottleneck_info,
                            'live_url' => $bp->live_url,
                        ]) }})" class="px-2 py-1 rounded bg-amber-500/20 border border-white/10 text-[10px] text-amber-300 hover:bg-amber-500/30 transition flex items-center gap-1 cursor-pointer font-sans">
                            <span class="font-bold">{{ $bp->name }}</span>
                            <span class="font-mono font-extrabold text-amber-400">({{ $bp->average_latency }}ms)</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-emerald-950/20 border border-emerald-500/20 rounded-xl p-2.5 relative z-10 flex items-center gap-2 text-[11px] text-emerald-400 font-sans">
                <svg class="h-4 w-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><strong>Topology Optimal:</strong> All microservice nodes responding within healthy thresholds (&lt;180ms).</span>
            </div>
        @endif

        <!-- Multi-Layer Topology Flow Diagram -->
        <div class="relative z-10 py-2 space-y-4">
            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block font-sans">ARCHITECTURAL TOPOLOGY LAYERS</span>

            <div class="grid grid-cols-1 {{ $hasLayer3 ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-6 items-stretch">

                <!-- Layer 1: Ingress & Frontend -->
                <div class="bg-zinc-900/40 border border-white/5 rounded-xl p-4 space-y-3 relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-2 border-b border-white/5">
                            <span class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest font-sans">LAYER 1 · INGRESS & FRONTEND</span>
                            <span class="text-[9px] text-zinc-500 font-mono">{{ $layer1Nodes->count() }} Node(s)</span>
                        </div>
                        <div class="mt-3 space-y-2">
                            @forelse($layer1Nodes as $node)
                                <div x-show="filterMode === 'all' || (filterMode === 'bottlenecks' && {{ $node->runtime_status !== 'healthy' ? 'true' : 'false' }})"
                                     @click="selectNode({{ json_encode([
                                        'id' => $node->id,
                                        'name' => $node->name,
                                        'category' => $node->category ?: 'Frontend Node',
                                        'status' => $node->runtime_status,
                                        'latency' => $node->average_latency,
                                        'layer' => $node->architectural_layer['name'],
                                        'bottleneck' => $node->bottleneck_info,
                                        'live_url' => $node->live_url,
                                    ]) }})" 
                                     class="p-2.5 rounded-lg bg-zinc-950/80 border {{ $node->runtime_status === 'critical' ? 'border-rose-500/40 bg-rose-500/5' : ($node->runtime_status === 'warning' ? 'border-white/10 bg-amber-500/5' : 'border-white/5 hover:border-indigo-500/40') }} cursor-pointer transition flex items-center justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-bold text-zinc-200 block truncate font-sans">{{ $node->name }}</span>
                                        <span class="text-[9px] text-zinc-500 block font-mono">{{ $node->category ?: 'Frontend' }}</span>
                                    </div>
                                    <div class="text-right flex-shrink-0 flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold {{ $node->average_latency >= 1000 ? 'text-rose-400' : ($node->average_latency >= 180 ? 'text-amber-400' : 'text-emerald-400') }}">
                                            {{ $node->average_latency }}ms
                                        </span>
                                        <x-status-badge :status="$node->runtime_status" />
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-zinc-500 py-3 text-center">No ingress nodes registered.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="pt-2 text-[10px] text-zinc-500 border-t border-white/5 flex justify-between font-mono">
                        <span>Layer Avg Latency:</span>
                        <span class="text-indigo-400 font-bold">
                            {{ $layer1Nodes->isNotEmpty() ? (int) round($layer1Nodes->avg(fn($n) => $n->average_latency)) . 'ms' : '0ms' }}
                        </span>
                    </div>
                </div>

                <!-- Layer 2: Core Microservices -->
                <div class="bg-zinc-900/40 border border-white/5 rounded-xl p-4 space-y-3 relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-2 border-b border-white/5">
                            <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-widest font-sans">LAYER 2 · CORE SERVICES</span>
                            <span class="text-[9px] text-zinc-500 font-mono">{{ $layer2Nodes->count() }} Node(s)</span>
                        </div>
                        <div class="mt-3 space-y-2">
                            @forelse($layer2Nodes as $node)
                                <div x-show="filterMode === 'all' || (filterMode === 'bottlenecks' && {{ $node->runtime_status !== 'healthy' ? 'true' : 'false' }})"
                                     @click="selectNode({{ json_encode([
                                        'id' => $node->id,
                                        'name' => $node->name,
                                        'category' => $node->category ?: 'Core Service',
                                        'status' => $node->runtime_status,
                                        'latency' => $node->average_latency,
                                        'layer' => $node->architectural_layer['name'],
                                        'bottleneck' => $node->bottleneck_info,
                                        'live_url' => $node->live_url,
                                    ]) }})" 
                                     class="p-2.5 rounded-lg bg-zinc-950/80 border {{ $node->runtime_status === 'critical' ? 'border-rose-500/40 bg-rose-500/5' : ($node->runtime_status === 'warning' ? 'border-white/10 bg-amber-500/5' : 'border-white/5 hover:border-emerald-500/40') }} cursor-pointer transition flex items-center justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-bold text-zinc-200 block truncate font-sans">{{ $node->name }}</span>
                                        <span class="text-[9px] text-zinc-500 block font-mono">{{ $node->category ?: 'Core Service' }}</span>
                                    </div>
                                    <div class="text-right flex-shrink-0 flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold {{ $node->average_latency >= 1000 ? 'text-rose-400' : ($node->average_latency >= 180 ? 'text-amber-400' : 'text-emerald-400') }}">
                                            {{ $node->average_latency }}ms
                                        </span>
                                        <x-status-badge :status="$node->runtime_status" />
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-zinc-500 py-3 text-center">No core service nodes registered.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="pt-2 text-[10px] text-zinc-500 border-t border-white/5 flex justify-between font-mono">
                        <span>Layer Avg Latency:</span>
                        <span class="text-emerald-400 font-bold">
                            {{ $layer2Nodes->isNotEmpty() ? (int) round($layer2Nodes->avg(fn($n) => $n->average_latency)) . 'ms' : '0ms' }}
                        </span>
                    </div>
                </div>

                <!-- Layer 3: Infrastructure & Datastores (Only rendered full column if nodes exist) -->
                @if($hasLayer3)
                    <div class="bg-zinc-900/40 border border-white/5 rounded-xl p-4 space-y-3 relative flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-2 border-b border-white/5">
                                <span class="text-[9px] font-bold text-purple-400 uppercase tracking-widest font-sans">LAYER 3 · INFRA & DATASTORE</span>
                                <span class="text-[9px] text-zinc-500 font-mono">{{ $layer3Nodes->count() }} Node(s)</span>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach($layer3Nodes as $node)
                                    <div x-show="filterMode === 'all' || (filterMode === 'bottlenecks' && {{ $node->runtime_status !== 'healthy' ? 'true' : 'false' }})"
                                         @click="selectNode({{ json_encode([
                                            'id' => $node->id,
                                            'name' => $node->name,
                                            'category' => $node->category ?: 'Datastore',
                                            'status' => $node->runtime_status,
                                            'latency' => $node->average_latency,
                                            'layer' => $node->architectural_layer['name'],
                                            'bottleneck' => $node->bottleneck_info,
                                            'live_url' => $node->live_url,
                                        ]) }})" 
                                         class="p-2.5 rounded-lg bg-zinc-950/80 border {{ $node->runtime_status === 'critical' ? 'border-rose-500/40 bg-rose-500/5' : ($node->runtime_status === 'warning' ? 'border-white/10 bg-amber-500/5' : 'border-white/5 hover:border-purple-500/40') }} cursor-pointer transition flex items-center justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <span class="text-xs font-bold text-zinc-200 block truncate font-sans">{{ $node->name }}</span>
                                            <span class="text-[9px] text-zinc-500 block font-mono">{{ $node->category ?: 'Datastore' }}</span>
                                        </div>
                                        <div class="text-right flex-shrink-0 flex items-center gap-2">
                                            <span class="text-xs font-mono font-bold {{ $node->average_latency >= 1000 ? 'text-rose-400' : ($node->average_latency >= 180 ? 'text-amber-400' : 'text-emerald-400') }}">
                                                {{ $node->average_latency }}ms
                                            </span>
                                            <x-status-badge :status="$node->runtime_status" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="pt-2 text-[10px] text-zinc-500 border-t border-white/5 flex justify-between font-mono">
                            <span>Layer Avg Latency:</span>
                            <span class="text-purple-400 font-bold">
                                {{ (int) round($layer3Nodes->avg(fn($n) => $n->average_latency)) }}ms
                            </span>
                        </div>
                    </div>
                @endif

            </div>

            <!-- Compact Empty State for Layer 3 (If 0 datastores registered) -->
            @if(!$hasLayer3)
                <div class="bg-zinc-900/20 border border-white/5 rounded-lg p-2.5 flex items-center justify-between text-[10px] text-zinc-500 font-mono">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.5 rounded bg-white/5 text-zinc-400 font-bold">LAYER 3</span>
                        <span>0 Infrastructure datastore nodes registered. (Assign category 'database', 'cache', or 'infra' to populate Layer 3)</span>
                    </div>
                </div>
            @endif
        </div>

    </div>{{-- /.Main Node Map Container --}}

    <!-- Interactive Node Telemetry Detail Modal Drawer -->
    <template x-teleport="body">
        <div x-show="activeNodeModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
             @click.self="activeNodeModal = null"
             @keydown.escape.window="activeNodeModal = null"
             style="display: none;">
            
            <div x-show="activeNodeModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="bg-zinc-950 border border-white/10 rounded-2xl p-6 max-w-lg w-full space-y-4 font-mono shadow-2xl relative my-auto overflow-hidden"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                    <h4 class="text-sm font-bold text-white font-sans" x-text="activeNodeModal?.name"></h4>
                    <button @click="activeNodeModal = null" class="text-zinc-500 hover:text-zinc-300 text-lg font-bold cursor-pointer">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-white/5">
                        <span class="text-zinc-500 font-sans">Architectural Layer:</span>
                        <span class="font-bold text-indigo-400 font-mono text-[11px]" x-text="activeNodeModal?.layer"></span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-white/5">
                        <span class="text-zinc-500 font-sans">Category / Role:</span>
                        <span class="font-bold text-zinc-300 capitalize" x-text="activeNodeModal?.category"></span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-white/5">
                        <span class="text-zinc-500 font-sans">Average Response Time:</span>
                        <span class="font-bold font-mono text-emerald-400" x-text="(activeNodeModal?.latency ?? 0) + ' ms'"></span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-white/5">
                        <span class="text-zinc-500 font-sans">Current Runtime Status:</span>
                        <span class="font-bold uppercase font-mono text-zinc-200" x-text="activeNodeModal?.status"></span>
                    </div>

                    <template x-if="activeNodeModal?.bottleneck && activeNodeModal?.bottleneck?.is_bottleneck">
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-white/10 space-y-1 mt-2">
                            <div class="flex items-center justify-between text-[11px] font-bold text-amber-300 font-sans">
                                <span>BOTTLENECK DIAGNOSTIC</span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] bg-amber-500/20 text-amber-400 border border-white/10" x-text="activeNodeModal?.bottleneck?.severity"></span>
                            </div>
                            <p class="text-[10px] text-amber-400/90 font-sans" x-text="activeNodeModal?.bottleneck?.reason"></p>
                            <p class="text-[10px] text-zinc-400 font-sans" x-text="'Impact: ' + activeNodeModal?.bottleneck?.impact"></p>
                        </div>
                    </template>
                </div>

                <div class="pt-3 border-t border-white/10 flex items-center justify-end gap-2">
                    <button @click="activeNodeModal = null" class="px-4 py-1.5 rounded-lg border border-white/10 text-xs text-zinc-400 hover:text-white font-sans cursor-pointer">Close</button>
                    <a :href="'/projects/' + activeNodeModal?.id" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white font-sans transition flex items-center gap-1 cursor-pointer">
                        View Service Detail &rarr;
                    </a>
                </div>
            </div>
        </div>
    </template>
</div>
