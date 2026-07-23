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
        <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    @elseif($status === 'warning')
        <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
    @elseif(in_array($status, ['critical', 'unreachable']))
        <svg class="w-3 h-3 flex-shrink-0 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    @elseif($status === 'maintenance')
        <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.075a3 3 0 00-3.484 2.179l-.277 1.15a.75.75 0 01-1.455-.35l.277-1.15A5 5 0 0110 11.66l.755-.756a3.5 3.5 0 114.95 4.95l-.756.755a5 5 0 01-3.535-1.544zm4.95-4.95l-2.5 2.5m-3-3l3-3m-7.208 7.208l2.5-2.5m-3-3l3-3" />
        </svg>
    @else
        <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
        </svg>
    @endif
    <span>{{ $status }}</span>
</span>
