@extends('layouts.app')

@section('title', 'Telemetry Logs')

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'telemetry', loading: true }" x-init="setTimeout(() => loading = false, 250)">

    <!-- Initial Page Loading Skeleton -->
    <div x-show="loading" class="space-y-6 font-mono">
        <div class="flex justify-between items-center pb-4 border-b border-white/5">
            <div class="space-y-2">
                <x-skeleton variant="text" width="w-48" height="h-6" />
                <x-skeleton variant="text" width="w-72" />
            </div>
        </div>
        <div class="bg-zinc-900/60 border border-white/5 rounded-xl p-4 space-y-3">
            <x-skeleton variant="table-row" count="6" />
        </div>
    </div>

    <!-- Main Content -->
    <div x-show="!loading" style="display: none;" class="space-y-6">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-white">System Logs & Audit Trail</h1>
            <p class="text-xs text-zinc-500 font-mono mt-1">Global log records of uptime checks and status transition histories.</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-white/5">
        <nav class="-mb-px flex gap-6 font-mono text-xs uppercase" aria-label="Tabs">
            <button @click="activeTab = 'telemetry'" 
                    :class="activeTab === 'telemetry' ? 'border-indigo-500 text-white font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer">
                Telemetry Pings
            </button>
            <button @click="activeTab = 'incidents'" 
                    :class="activeTab === 'incidents' ? 'border-indigo-500 text-white font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                    class="border-b-2 py-2.5 px-1 tracking-wider transition-all cursor-pointer">
                Status Transitions
            </button>
        </nav>
    </div>

    <!-- Tab 1: Telemetry Logs (Metrics Snapshots) -->
    <div x-show="activeTab === 'telemetry'" class="space-y-6">
        <div class="bg-zinc-900/20 border border-white/5 rounded-lg overflow-hidden">
            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block px-6 py-4 border-b border-white/5 font-mono bg-zinc-950/10">Recent {{ $metricsLogs->count() }} Uptime & Telemetry Snapshots</span>

            @if($metricsLogs->isEmpty())
                <p class="text-xs text-zinc-500 font-mono italic text-center py-12">No telemetry snapshots logged yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/5 text-left text-xs align-middle font-mono">
                        <thead class="bg-zinc-950/50 uppercase tracking-widest text-[9px] font-bold text-zinc-500">
                            <tr>
                                <th scope="col" class="py-3 px-6">Timestamp</th>
                                <th scope="col" class="py-3 px-3">Service Name</th>
                                <th scope="col" class="py-3 px-3 text-center">HTTP</th>
                                <th scope="col" class="py-3 px-3">Health Status</th>
                                <th scope="col" class="py-3 px-3 text-right">Traffic</th>
                                <th scope="col" class="py-3 px-3 text-right">Latency</th>
                                <th scope="col" class="py-3 px-3 text-right">Error Rate</th>
                                <th scope="col" class="py-3 px-6">Log Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-zinc-300">
                            @foreach($metricsLogs as $log)
                                @php
                                    $statusColor = match($log->health_status) {
                                        'healthy' => 'text-status-healthy-text bg-status-healthy-bg border-status-healthy-border',
                                        'warning' => 'text-status-warning-text bg-status-warning-bg border-status-warning-border',
                                        'critical', 'unreachable' => 'text-status-critical-text bg-status-critical-bg border-status-critical-border',
                                        'maintenance' => 'text-status-sky-text bg-status-sky-bg border-status-sky-border',
                                        default => 'text-status-neutral-text bg-status-neutral-bg border-status-neutral-border',
                                    };
                                @endphp
                                <tr class="hover:bg-white/2 transition">
                                    <td class="py-3.5 px-6 whitespace-nowrap text-zinc-500 text-[10px]">
                                        {{ $log->checked_at ? $log->checked_at->toDateTimeString() : '—' }}
                                    </td>
                                    <td class="py-3.5 px-3 font-semibold text-zinc-200">
                                        @if($log->project)
                                            <a href="{{ route('projects.show', $log->project) }}" class="hover:text-indigo-400 transition-colors">
                                                {{ $log->project->name }}
                                            </a>
                                        @else
                                            <span class="text-zinc-600">[Deleted Project]</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="{{ $log->http_status >= 400 ? 'text-status-critical-text font-bold' : ($log->http_status === 200 ? 'text-status-healthy-text' : 'text-zinc-400') }}">
                                            {{ $log->http_status ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded border text-[9px] font-semibold uppercase tracking-wider {{ $statusColor }}">
                                            {{ $log->health_status }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-right text-zinc-400">
                                        {{ $log->requests_count !== null ? number_format($log->requests_count) : '—' }}
                                    </td>
                                    <td class="py-3.5 px-3 text-right text-zinc-400">
                                        {{ $log->avg_response_time_ms !== null ? $log->avg_response_time_ms . ' ms' : '—' }}
                                    </td>
                                    <td class="py-3.5 px-3 text-right {{ $log->error_rate > 0 ? 'text-status-critical-text font-bold' : 'text-zinc-400' }}">
                                        {{ $log->error_rate !== null ? number_format($log->error_rate, 1) . '%' : '—' }}
                                    </td>
                                    <td class="py-3.5 px-6 italic text-zinc-500 leading-relaxed font-sans text-xs max-w-xs truncate" title="{{ $log->error_message }}">
                                        {{ $log->error_message ?: 'Successful ping scan.' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Tab 2: Incident & Status Change Logs (Project Updates) -->
    <div x-show="activeTab === 'incidents'" class="space-y-6" style="display: none;">
        <div class="bg-zinc-900/20 border border-white/5 rounded-lg overflow-hidden">
            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest block px-6 py-4 border-b border-white/5 font-mono bg-zinc-950/10">Recent {{ $statusLogs->count() }} Project Phase & Override History Trails</span>

            @if($statusLogs->isEmpty())
                <p class="text-xs text-zinc-500 font-mono italic text-center py-12">No status change events logged yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/5 text-left text-xs align-middle font-mono">
                        <thead class="bg-zinc-950/50 uppercase tracking-widest text-[9px] font-bold text-zinc-500">
                            <tr>
                                <th scope="col" class="py-3 px-6">Timestamp</th>
                                <th scope="col" class="py-3 px-3">Service Name</th>
                                <th scope="col" class="py-3 px-3">Transition Change</th>
                                <th scope="col" class="py-3 px-6">Event Note / Comment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-zinc-300">
                            @foreach($statusLogs as $update)
                                <tr class="hover:bg-white/2 transition">
                                    <td class="py-3.5 px-6 whitespace-nowrap text-zinc-500 text-[10px]">
                                        {{ $update->created_at->toDateTimeString() }} ({{ $update->created_at->diffForHumans() }})
                                    </td>
                                    <td class="py-3.5 px-3 font-semibold text-zinc-200">
                                        @if($update->project)
                                            <a href="{{ route('projects.show', $update->project) }}" class="hover:text-indigo-400 transition-colors">
                                                {{ $update->project->name }}
                                            </a>
                                        @else
                                            <span class="text-zinc-600">[Deleted Project]</span>
                                        @endif
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
                </div>
            @endif
        </div>
    </div>

</div>
</div>
@endsection
