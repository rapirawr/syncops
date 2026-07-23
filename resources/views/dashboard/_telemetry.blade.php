{{-- Fragment: host hardware telemetry. Rendered by DashboardController@fragmentTelemetry --}}
<div class="bg-zinc-900/20 border border-white/5 rounded-xl p-5 font-mono space-y-4">
    <div class="flex items-center justify-between gap-4 pb-3 border-b border-white/5">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-zinc-200 uppercase tracking-wider">Local Host Server Hardware Telemetry</span>
        </div>
        <span class="text-[10px] text-zinc-500">Node: <span class="text-zinc-300 font-bold">{{ $serverMetrics['system']['hostname'] }}</span></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 animate-stagger">
        <!-- CPU Widget -->
        <div class="bg-zinc-950/40 border border-white/5 rounded-lg p-4 flex flex-col justify-between space-y-3 hover-lift shimmer-card stagger-1 transition-all">
            <div>
                <div class="flex items-center justify-between text-[9px] font-bold text-zinc-500 uppercase tracking-widest mb-1">
                    <span>CPU Processor</span>
                    <span class="{{ $serverMetrics['cpu']['usage_pct'] >= 85 ? 'text-rose-400 font-bold animate-pulse' : ($serverMetrics['cpu']['usage_pct'] >= 60 ? 'text-amber-400 font-bold' : 'text-emerald-400 font-bold') }}">
                        {{ $serverMetrics['cpu']['usage_pct'] }}% LOAD
                    </span>
                </div>
                <span class="text-xs font-bold text-zinc-200 block truncate" title="{{ $serverMetrics['cpu']['name'] }}">
                    {{ $serverMetrics['cpu']['name'] }}
                </span>
                @if($serverMetrics['cpu']['cores'])
                    <span class="text-[10px] text-zinc-500 block mt-0.5">
                        {{ $serverMetrics['cpu']['cores'] }} Cores / {{ $serverMetrics['cpu']['threads'] ?? $serverMetrics['cpu']['cores'] }} Threads
                    </span>
                @endif
            </div>
            <!-- CPU Progress Bar -->
            <div>
                <div class="w-full h-1.5 bg-zinc-800/80 rounded-full overflow-hidden p-0.5">
                    <div class="h-full {{ $serverMetrics['cpu']['usage_pct'] >= 85 ? 'bg-rose-500' : ($serverMetrics['cpu']['usage_pct'] >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }} rounded-full transition-all duration-700 ease-out" style="width: {{ $serverMetrics['cpu']['usage_pct'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- RAM Widget -->
        <div class="bg-zinc-950/40 border border-white/5 rounded-lg p-4 flex flex-col justify-between space-y-3 hover-lift shimmer-card stagger-2 transition-all">
            <div>
                <div class="flex items-center justify-between text-[9px] font-bold text-zinc-500 uppercase tracking-widest mb-1">
                    <span>RAM Memory</span>
                    <span class="{{ $serverMetrics['ram']['usage_pct'] >= 85 ? 'text-rose-400 font-bold animate-pulse' : ($serverMetrics['ram']['usage_pct'] >= 60 ? 'text-amber-400 font-bold' : 'text-indigo-400 font-bold') }}">
                        {{ $serverMetrics['ram']['usage_pct'] }}% USED
                    </span>
                </div>
                <span class="text-xs font-bold text-zinc-200 block">
                    {{ $serverMetrics['ram']['used_gb'] }} GB / {{ $serverMetrics['ram']['total_gb'] }} GB
                </span>
                <span class="text-[10px] text-zinc-500 block mt-0.5">
                    {{ $serverMetrics['ram']['free_gb'] }} GB Free Memory
                </span>
            </div>
            <!-- RAM Progress Bar -->
            <div>
                <div class="w-full h-1.5 bg-zinc-800/80 rounded-full overflow-hidden p-0.5">
                    <div class="h-full {{ $serverMetrics['ram']['usage_pct'] >= 85 ? 'bg-rose-500' : ($serverMetrics['ram']['usage_pct'] >= 60 ? 'bg-amber-500' : 'bg-indigo-500') }} rounded-full transition-all duration-700 ease-out" style="width: {{ $serverMetrics['ram']['usage_pct'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- GPU Widget -->
        <div class="bg-zinc-950/40 border border-white/5 rounded-lg p-4 flex flex-col justify-between space-y-3 hover-lift shimmer-card stagger-3 transition-all">
            <div>
                <div class="flex items-center justify-between text-[9px] font-bold text-zinc-500 uppercase tracking-widest mb-1">
                    <span>GPU Hardware</span>
                    <span class="text-emerald-400 font-bold uppercase flex items-center gap-1">
                        {{ $serverMetrics['gpu']['status'] }}
                    </span>
                </div>
                <span class="text-xs font-bold text-zinc-200 block truncate" title="{{ $serverMetrics['gpu']['name'] }}">
                    {{ $serverMetrics['gpu']['name'] }}
                </span>
                <span class="text-[10px] text-zinc-500 block mt-0.5">
                    {{ $serverMetrics['gpu']['vram_gb'] ? $serverMetrics['gpu']['vram_gb'] . ' GB VRAM' : 'System Shared Memory' }}
                </span>
            </div>
            <div class="text-[9px] text-zinc-600 uppercase tracking-wider font-bold">
                Graphics Engine Enabled
            </div>
        </div>

        <!-- Host OS & Server Stack -->
        <div class="bg-zinc-950/40 border border-white/5 rounded-lg p-4 flex flex-col justify-between space-y-3 hover-lift shimmer-card stagger-4 transition-all">
            <div>
                <div class="flex items-center justify-between text-[9px] font-bold text-zinc-500 uppercase tracking-widest mb-1">
                    <span>Host Operating System</span>
                    <span class="text-zinc-400 font-bold">PHP {{ $serverMetrics['system']['php_version'] }}</span>
                </div>
                <span class="text-xs font-bold text-zinc-200 block truncate" title="{{ $serverMetrics['system']['os'] }}">
                    {{ $serverMetrics['system']['os'] }}
                </span>
                <span class="text-[10px] text-zinc-500 block mt-0.5 truncate">
                    {{ $serverMetrics['system']['server_software'] }}
                </span>
            </div>
            <div class="text-[9px] text-zinc-600 uppercase tracking-wider font-bold">
                Server Operational
            </div>
        </div>
    </div>
</div>
