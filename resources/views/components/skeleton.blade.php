@props([
    'variant' => 'box',
    'width' => null,
    'height' => null,
    'count' => 1,
])

@for ($i = 0; $i < $count; $i++)
    @if ($variant === 'card' || $variant === 'stat-box')
        <div {{ $attributes->merge(['class' => 'bg-zinc-900/30 border border-white/5 rounded-xl p-4 font-mono space-y-3']) }}>
            <div class="flex items-center justify-between">
                <div class="skeleton h-2.5 w-24"></div>
                <div class="skeleton h-5 w-5 rounded"></div>
            </div>
            <div class="flex items-baseline gap-2">
                <div class="skeleton h-7 w-16"></div>
                <div class="skeleton h-2.5 w-12"></div>
            </div>
            <div class="skeleton h-2 w-3/4"></div>
        </div>
    @elseif ($variant === 'telemetry-card')
        <div {{ $attributes->merge(['class' => 'bg-zinc-900/20 border border-white/5 rounded-xl p-4 space-y-3 font-mono']) }}>
            <div class="flex items-center justify-between pb-2 border-b border-white/5">
                <div class="skeleton h-2.5 w-20"></div>
                <div class="skeleton h-2.5 w-12"></div>
            </div>
            <div class="skeleton h-6 w-3/4"></div>
            <div class="skeleton h-2 w-1/2"></div>
            <div class="skeleton h-1.5 w-full rounded-full mt-2"></div>
        </div>
    @elseif ($variant === 'table-row')
        <tr {{ $attributes->merge(['class' => 'border-b border-white/5']) }}>
            <td class="py-3.5 px-4">
                <div class="flex items-center gap-3">
                    <div class="skeleton h-7 w-7 rounded-lg shrink-0"></div>
                    <div class="space-y-1.5 flex-1">
                        <div class="skeleton h-3.5 w-32"></div>
                        <div class="skeleton h-2.5 w-24"></div>
                    </div>
                </div>
            </td>
            <td class="py-3.5 px-4"><div class="skeleton h-5 w-16 rounded-full"></div></td>
            <td class="py-3.5 px-4"><div class="skeleton h-3.5 w-20"></div></td>
            <td class="py-3.5 px-4"><div class="skeleton h-3.5 w-16 ml-auto"></div></td>
            <td class="py-3.5 px-4"><div class="skeleton h-3.5 w-12 ml-auto"></div></td>
        </tr>
    @elseif ($variant === 'chart')
        <div {{ $attributes->merge(['class' => 'bg-zinc-900/30 border border-white/5 rounded-xl p-5 space-y-4']) }}>
            <div class="flex items-center justify-between">
                <div class="space-y-1.5">
                    <div class="skeleton h-3.5 w-48"></div>
                    <div class="skeleton h-2.5 w-32"></div>
                </div>
                <div class="skeleton h-7 w-24 rounded-lg"></div>
            </div>
            <div class="h-44 flex items-end justify-between gap-2 pt-6 pb-2 px-2 border-b border-white/5">
                @for ($c = 0; $c < 12; $c++)
                    @php $randomH = [20, 40, 60, 35, 75, 50, 85, 30, 65, 45, 90, 55][$c % 12]; @endphp
                    <div class="skeleton w-full rounded-t" style="height: {{ $randomH }}%"></div>
                @endfor
            </div>
            <div class="flex justify-between">
                <div class="skeleton h-2 w-16"></div>
                <div class="skeleton h-2 w-16"></div>
                <div class="skeleton h-2 w-16"></div>
            </div>
        </div>
    @elseif ($variant === 'dependency-map')
        <div {{ $attributes->merge(['class' => 'bg-zinc-950 border border-white/5 rounded-2xl p-5 space-y-4 font-mono shadow-2xl relative overflow-hidden']) }}>
            <!-- Header Skeleton -->
            <div class="flex items-center justify-between pb-3 border-b border-white/5">
                <div class="flex items-center gap-2">
                    <div class="skeleton h-6 w-6 rounded"></div>
                    <div class="space-y-1.5">
                        <div class="skeleton h-3.5 w-64"></div>
                        <div class="skeleton h-2.5 w-80"></div>
                    </div>
                </div>
            </div>

            <!-- Nodes & Arrow Skeleton -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center py-2">
                <div class="bg-zinc-900/40 border border-white/10 rounded-xl p-4 space-y-3">
                    <div class="skeleton h-2.5 w-28"></div>
                    <div class="skeleton h-4 w-48"></div>
                    <div class="flex justify-between pt-1 border-t border-white/5">
                        <div class="skeleton h-2.5 w-20"></div>
                        <div class="skeleton h-2.5 w-16"></div>
                    </div>
                </div>

                <div class="hidden md:flex flex-col items-center justify-center space-y-1">
                    <div class="skeleton h-1 w-full rounded"></div>
                    <div class="skeleton h-2.5 w-24"></div>
                </div>

                <div class="bg-zinc-900/40 border border-white/10 rounded-xl p-4 space-y-3">
                    <div class="skeleton h-2.5 w-28"></div>
                    <div class="skeleton h-4 w-48"></div>
                    <div class="flex justify-between pt-1 border-t border-white/5">
                        <div class="skeleton h-2.5 w-20"></div>
                        <div class="skeleton h-2.5 w-16"></div>
                    </div>
                </div>
            </div>

            <!-- Bottom Target Nodes Grid Skeleton -->
            <div class="pt-4 border-t border-white/5 space-y-3">
                <div class="skeleton h-2.5 w-60"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @for ($s = 0; $s < 6; $s++)
                        <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-3 space-y-1.5">
                            <div class="skeleton h-3.5 w-32"></div>
                            <div class="skeleton h-2.5 w-24"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @elseif ($variant === 'avatar' || $variant === 'circle')
        <div {{ $attributes->merge(['class' => 'skeleton rounded-full shrink-0 ' . ($width ?: 'w-8') . ' ' . ($height ?: 'h-8')]) }}></div>
    @elseif ($variant === 'text')
        <div {{ $attributes->merge(['class' => 'skeleton rounded ' . ($width ?: 'w-full') . ' ' . ($height ?: 'h-3')]) }}></div>
    @else
        <div {{ $attributes->merge(['class' => 'skeleton rounded ' . ($width ?: 'w-full') . ' ' . ($height ?: 'h-4')]) }}></div>
    @endif
@endfor
