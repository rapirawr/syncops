{{-- Skeleton for the 30-box uptime history strip.
     variant "card" = flex-1 h-3 boxes (project card), "table" = w-1.5 h-4 boxes (table row) --}}
@props(['bars' => 30, 'variant' => 'card'])
<div {{ $attributes->merge(['class' => 'flex items-center gap-[2px] animate-pulse' . ($variant === 'card' ? ' w-full justify-between' : '')]) }}>
    @for($i = 0; $i < $bars; $i++)
        <span class="{{ $variant === 'card' ? 'flex-1 h-3' : 'w-1.5 h-4' }} rounded-[1px] bg-white/10"></span>
    @endfor
</div>
