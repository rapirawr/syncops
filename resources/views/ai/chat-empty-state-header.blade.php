@php
    $hour = now()->hour;
    if ($hour >= 5 && $hour < 12) {
        $greeting = "Good morning. What shall we monitor today?";
    } elseif ($hour >= 12 && $hour < 17) {
        $greeting = "Good afternoon. Checking the systems?";
    } elseif ($hour >= 17 && $hour < 22) {
        $greeting = "Clocking in for the evening shift.";
    } else {
        $greeting = "Burning the midnight oil? Let's check some logs.";
    }
@endphp

<div x-show="messages.length === 0" 
     x-transition:enter="transition ease-out duration-500"
     x-transition:enter-start="opacity-0 -translate-y-4" 
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 translate-y-0" 
     x-transition:leave-end="opacity-0 -translate-y-4"
     class="flex flex-col items-center justify-center text-center w-full max-w-2xl px-4 select-none">

    <!-- 1. Top: Strands Component (No Card Container) -->
    <div class="w-full h-44 sm:h-56 relative overflow-hidden pointer-events-none">
        <div id="strands-root" 
             class="absolute inset-0 w-full h-full pointer-events-none"
             data-colors='["#646dc8","#646dc8","#7d8ea0"]'
             data-count="3"
             data-speed="0.5"
             data-amplitude="1"
             data-waviness="1"
             data-thickness="0.7"
             data-glow="2.6"
             data-taper="3"
             data-spread="1"
             data-intensity="0.6"
             data-saturation="1.5"
             data-opacity="1"
             data-scale="1.5"
             data-glass="false"
             data-refraction="1"
             data-dispersion="1"
             data-glass-size="1">
        </div>
    </div>

    <!-- 2. Middle: Greeting -->
    <div class="my-4">
        <h1 class="text-2xl sm:text-3xl text-zinc-100 tracking-tight font-serif select-text font-normal">
            {{ $greeting }}
        </h1>
    </div>
</div>
