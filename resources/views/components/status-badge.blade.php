@props(['status', 'reason' => null])

@php
    $status = strtolower($status);
    $config = match($status) {
        'healthy' => [
            'bg' => 'bg-status-healthy-bg text-status-healthy-text border-status-healthy-border',
        ],
        'warning' => [
            'bg' => 'bg-status-warning-bg text-status-warning-text border-status-warning-border',
        ],
        'critical', 'unreachable' => [
            'bg' => 'bg-status-critical-bg text-status-critical-text border-status-critical-border',
        ],
        'maintenance' => [
            'bg' => 'bg-status-sky-bg text-status-sky-text border-status-sky-border',
        ],
        default => [
            'bg' => 'bg-status-neutral-bg text-status-neutral-text border-status-neutral-border',
        ],
    };
    $hasReason = $reason && in_array($status, ['warning', 'unreachable', 'critical']);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2 py-0.5 rounded border text-[10px] font-semibold tracking-wider font-mono uppercase ' . $config['bg'] . ($hasReason ? ' cursor-help' : '')]) }}
    @if($hasReason) data-tooltip="{{ $reason }}" @endif>
    @if($status === 'healthy')
        <span class="relative flex h-2 w-2 flex-shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
    @elseif($status === 'warning')
        <span class="relative flex h-2 w-2 flex-shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
        </span>
    @elseif(in_array($status, ['critical', 'unreachable']))
        <span class="relative flex h-2 w-2 flex-shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-90" style="animation-duration: 1s;"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
        </span>
    @elseif($status === 'maintenance')
        <span class="relative flex h-2 w-2 flex-shrink-0">
            <span class="relative inline-flex rounded-full h-2 w-2 bg-sky-400 animate-pulse"></span>
        </span>
    @else
        <span class="h-2 w-2 rounded-full bg-zinc-500 flex-shrink-0"></span>
    @endif
    <span>{{ $status }}</span>
</span>
