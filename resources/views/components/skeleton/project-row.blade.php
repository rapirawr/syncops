{{-- Skeleton mirroring one project row in the table view --}}
<tr class="border-l-2 border-l-white/10">
    {{-- Status badge --}}
    <td class="py-3.5 pl-4 pr-3 whitespace-nowrap">
        <div class="skeleton h-5 w-20 rounded"></div>
    </td>
    {{-- Service name + slug --}}
    <td class="py-3.5 px-3">
        <div class="skeleton h-3.5 w-28"></div>
        <div class="skeleton h-2 w-20 mt-1.5"></div>
    </td>
    {{-- Category / stage --}}
    <td class="py-3.5 px-3">
        <div class="skeleton h-2.5 w-24"></div>
    </td>
    {{-- Uptime 30-bar strip + pct --}}
    <td class="py-3.5 px-3 text-center whitespace-nowrap">
        <div class="inline-flex items-center gap-2">
            <x-skeleton.uptime-bar variant="table" />
            <div class="skeleton h-2.5 w-8"></div>
        </div>
    </td>
    {{-- IP --}}
    <td class="py-3.5 px-3"><div class="skeleton h-2.5 w-20"></div></td>
    {{-- Traffic --}}
    <td class="py-3.5 px-3"><div class="skeleton h-2.5 w-12 ml-auto"></div></td>
    {{-- Error rate --}}
    <td class="py-3.5 px-3"><div class="skeleton h-2.5 w-10 ml-auto"></div></td>
    {{-- Latency --}}
    <td class="py-3.5 px-3"><div class="skeleton h-2.5 w-12 ml-auto"></div></td>
    {{-- Last sync --}}
    <td class="py-3.5 px-3"><div class="skeleton h-2.5 w-16 ml-auto"></div></td>
    {{-- Tech stack --}}
    <td class="py-3.5 px-3">
        <div class="flex items-center gap-1.5">
            <div class="skeleton h-4 w-12 rounded"></div>
            <div class="skeleton h-4 w-14 rounded"></div>
        </div>
    </td>
    {{-- Actions --}}
    <td class="py-3.5 pr-6 pl-3 text-right whitespace-nowrap">
        <div class="inline-flex items-center gap-3">
            <div class="skeleton h-3.5 w-3.5 rounded"></div>
            <div class="skeleton h-3.5 w-3.5 rounded"></div>
            <div class="skeleton h-3.5 w-3.5 rounded"></div>
        </div>
    </td>
</tr>
