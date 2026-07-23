@extends('layouts.app')

@section('title', 'Systems Dashboard')

@section('content')
<div class="space-y-6" x-data="dashboardShell()">

    {{-- ═══════ SECTION 1: Alert bar + KPI stats ═══════ --}}
    <div>
        {{-- Skeleton (initial load only) --}}
        <div x-show="!loaded.stats">
            <div class="flex items-center gap-2 px-4 py-2 bg-zinc-950 border border-white/5 rounded-lg">
                <x-skeleton variant="circle" width="w-3.5" height="h-3.5" />
                <x-skeleton variant="text" width="w-52" />
                <x-skeleton variant="text" width="w-72" class="hidden sm:block" />
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                <x-skeleton variant="card" count="4" />
            </div>
        </div>
        {{-- Real content --}}
        <div x-show="loaded.stats" id="statsContainer" x-ref="statsContainer"></div>
    </div>

    {{-- ═══════ SECTION 2: Host Hardware Telemetry ═══════ --}}
    <div>
        {{-- Skeleton --}}
        <div x-show="!loaded.telemetry" class="bg-zinc-900/20 border border-white/5 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between gap-4 pb-3 border-b border-white/5">
                <div class="flex items-center gap-2">
                    <x-skeleton variant="circle" width="w-2" height="h-2" />
                    <x-skeleton variant="text" width="w-64" />
                </div>
                <x-skeleton variant="text" width="w-24" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <x-skeleton variant="telemetry-card" count="4" />
            </div>
        </div>
        {{-- Real content --}}
        <div x-show="loaded.telemetry" id="telemetryContainer" x-ref="telemetryContainer"></div>
    </div>

    {{-- ═══════ SECTION 3: Microservice Dependency Map & Bottleneck Visualizer ═══════ --}}
    <div>
        @include('dashboard._dependency_map')
    </div>

    {{-- ═══════ Filters & View Toggle Row (server-rendered, instant) ═══════ --}}
    <div class="bg-zinc-950 border border-white/5 rounded-lg p-3 flex flex-col lg:flex-row lg:items-center justify-between gap-4 relative z-10">
        <form action="{{ route('dashboard') }}" method="GET" x-ref="filterForm" class="flex flex-wrap items-center gap-2.5 flex-1">
            <!-- Search -->
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-zinc-600">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" id="project-search" placeholder="Filter by project... (Ctrl+K)" value="{{ request('search') }}"
                       class="block w-full rounded border border-white/10 bg-zinc-950 pl-8 pr-3 py-1 text-[11px] text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
            </div>

            <!-- Category Custom Dropdown -->
            <div x-data="{
                     open: false,
                     selected: '{{ is_string(request('category')) && request('category') ? ucfirst(request('category')) : 'All Categories' }}',
                     select(value, label) {
                         $refs.categoryInput.value = value;
                         $refs.filterForm.submit();
                     }
                 }"
                 @click.outside="open = false"
                 class="relative inline-block text-left">

                <input type="hidden" name="category" x-ref="categoryInput" value="{{ is_string(request('category')) ? request('category') : '' }}">

                <button type="button" @click="open = !open"
                        class="flex items-center justify-between gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] text-zinc-200 hover:text-white transition font-mono min-w-[140px] focus:outline-none cursor-pointer">
                    <span x-text="selected"></span>
                    <svg class="h-3 w-3 text-zinc-400 transition-transform duration-300" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                     class="absolute left-0 mt-1.5 w-48 rounded-xl border border-white/10 bg-zinc-900/90 backdrop-blur-xl p-1 shadow-2xl z-50 overflow-hidden flex flex-col gap-1"
                     style="display: none;">

                    <button type="button" @click="select('', 'All Categories')"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] font-mono transition cursor-pointer"
                            :class="selected === 'All Categories' ? 'bg-white/10 text-white' : 'text-zinc-300 hover:bg-white/5 hover:text-white'">
                        All Categories
                    </button>
                    @foreach($categories as $category)
                        @php $label = is_string($category) ? ucfirst($category) : (string) $category; @endphp
                        <button type="button" @click="select('{{ $category }}', '{{ $label }}')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] font-mono transition cursor-pointer"
                                :class="selected === '{{ $label }}' ? 'bg-white/10 text-white' : 'text-zinc-300 hover:bg-white/5 hover:text-white'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Dev Status Custom Dropdown -->
            <div x-data="{
                     open: false,
                     selected: '{{ request('status') ? [
                         'planning' => 'Planning',
                         'in_progress' => 'In Progress',
                         'on_hold' => 'On Hold',
                         'done' => 'Done',
                         'archived' => 'Archived'
                     ][request('status')] ?? 'All Stages' : 'All Stages' }}',
                     select(value, label) {
                         $refs.statusInput.value = value;
                         $refs.filterForm.submit();
                     }
                 }"
                 @click.outside="open = false"
                 class="relative inline-block text-left">

                <input type="hidden" name="status" x-ref="statusInput" value="{{ request('status') }}">

                <button type="button" @click="open = !open"
                        class="flex items-center justify-between gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] text-zinc-200 hover:text-white transition font-mono min-w-[125px] focus:outline-none cursor-pointer">
                    <span x-text="selected"></span>
                    <svg class="h-3 w-3 text-zinc-400 transition-transform duration-300" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                     class="absolute left-0 mt-1.5 w-48 rounded-xl border border-white/10 bg-zinc-900/90 backdrop-blur-xl p-1 shadow-2xl z-50 overflow-hidden flex flex-col gap-1"
                     style="display: none;">

                    @foreach([
                        '' => 'All Stages',
                        'planning' => 'Planning',
                        'in_progress' => 'In Progress',
                        'on_hold' => 'On Hold',
                        'done' => 'Done',
                        'archived' => 'Archived'
                    ] as $val => $lbl)
                        <button type="button" @click="select('{{ $val }}', '{{ $lbl }}')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] font-mono transition cursor-pointer"
                                :class="selected === '{{ $lbl }}' ? 'bg-white/10 text-white' : 'text-zinc-300 hover:bg-white/5 hover:text-white'">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Sort Custom Dropdown -->
            <div x-data="{
                     open: false,
                     selected: '{{ request('sort') === 'alphabetical' ? 'Sort: Alphabetical' : (request('sort') === 'health' ? 'Sort: Health Severity' : 'Sort: Custom Order') }}',
                     select(value, label) {
                         $refs.sortInput.value = value;
                         $refs.filterForm.submit();
                     }
                 }"
                 @click.outside="open = false"
                 class="relative inline-block text-left">

                <input type="hidden" name="sort" x-ref="sortInput" value="{{ request('sort', 'custom') }}">

                <button type="button" @click="open = !open"
                        class="flex items-center justify-between gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] text-zinc-200 hover:text-white transition font-mono min-w-[165px] focus:outline-none cursor-pointer">
                    <span x-text="selected"></span>
                    <svg class="h-3 w-3 text-zinc-400 transition-transform duration-300" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                     class="absolute left-0 mt-1.5 w-48 rounded-xl border border-white/10 bg-zinc-900/90 backdrop-blur-xl p-1 shadow-2xl z-50 overflow-hidden flex flex-col gap-1"
                     style="display: none;">

                    <button type="button" @click="select('custom', 'Sort: Custom Order')"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] font-mono transition cursor-pointer"
                            :class="selected === 'Sort: Custom Order' ? 'bg-white/10 text-white' : 'text-zinc-300 hover:bg-white/5 hover:text-white'">
                        Sort: Custom Order
                    </button>
                    <button type="button" @click="select('health', 'Sort: Health Severity')"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] font-mono transition cursor-pointer"
                            :class="selected === 'Sort: Health Severity' ? 'bg-white/10 text-white' : 'text-zinc-300 hover:bg-white/5 hover:text-white'">
                        Sort: Health Severity
                    </button>
                    <button type="button" @click="select('alphabetical', 'Sort: Alphabetical')"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] font-mono transition cursor-pointer"
                            :class="selected === 'Sort: Alphabetical' ? 'bg-white/10 text-white' : 'text-zinc-300 hover:bg-white/5 hover:text-white'">
                        Sort: Alphabetical
                    </button>
                </div>
            </div>

            @if(request()->anyFilled(['search', 'category', 'status', 'sort']))
                <a href="{{ route('dashboard') }}" class="text-[10px] text-indigo-400 hover:text-indigo-300 font-mono">Reset filters</a>
            @endif
        </form>

        <div class="flex items-center gap-3">
            <!-- View Mode Switcher -->
            <div class="inline-flex rounded border border-white/10 p-0.5 bg-zinc-900/50 text-zinc-500 font-mono text-[9px] uppercase font-bold">
                <button type="button" @click="setViewMode('table')"
                        :class="viewMode === 'table' ? 'bg-zinc-800 text-white border border-white/5' : 'hover:text-zinc-300'"
                        class="px-2 py-0.5 rounded transition">
                    Table
                </button>
                <button type="button" @click="setViewMode('grid')"
                        :class="viewMode === 'grid' ? 'bg-zinc-800 text-white border border-white/5' : 'hover:text-zinc-300'"
                        class="px-2 py-0.5 rounded transition">
                    Cards
                </button>
            </div>

            <!-- Sync All Button (manual sync: spinner only, data stays visible — no skeleton) -->
            <div class="inline" x-data="{ loading: false }">
                <button type="button" @click="if (!loading) { loading = true; await runSyncAllSequence(); loading = false; }" :disabled="loading || !loaded.projects" class="inline-flex items-center gap-1.5 rounded border border-white/10 bg-zinc-900 px-3 py-1.5 text-[11px] font-semibold text-zinc-350 hover:bg-zinc-800 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="h-3.5 w-3.5 text-zinc-400" :class="loading ? 'animate-spin text-indigo-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span x-text="loading ? 'Syncing...' : 'Sync All'">Sync All</span>
                </button>
            </div>

            <a href="#" @click.prevent="$dispatch('open-create-modal')" class="inline-flex items-center gap-1.5 rounded border border-white/10 bg-zinc-900 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-zinc-800 transition">
                <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Project
            </a>
        </div>
    </div>

    {{-- ═══════ SECTION 3: Projects (table + grid) ═══════ --}}
    <div>
        {{-- Skeleton: table rows when table mode, cards when grid mode --}}
        <div x-show="!loaded.projects">
            <div x-show="viewMode === 'table'" class="overflow-x-auto bg-zinc-900/20 border border-white/5 rounded-lg shadow-sm">
                <table class="min-w-full divide-y divide-white/5 text-left text-xs align-middle">
                    <thead class="bg-zinc-950/80 uppercase tracking-widest text-[9px] font-bold text-zinc-500 font-mono">
                        <tr>
                            <th scope="col" class="py-3.5 pl-6 pr-3">Status</th>
                            <th scope="col" class="py-3.5 px-3">Service Name</th>
                            <th scope="col" class="py-3.5 px-3">Category/Stages</th>
                            <th scope="col" class="py-3.5 px-3 text-center">Uptime History (Last 30 Checks)</th>
                            <th scope="col" class="py-3.5 px-3">IP Address</th>
                            <th scope="col" class="py-3.5 px-3 text-right">Traffic</th>
                            <th scope="col" class="py-3.5 px-3 text-right">Error Rate</th>
                            <th scope="col" class="py-3.5 px-3 text-right">Latency</th>
                            <th scope="col" class="py-3.5 px-3 text-right">Last Sync</th>
                            <th scope="col" class="py-3.5 px-3">Tech Stack</th>
                            <th scope="col" class="py-3.5 pr-6 pl-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <x-skeleton variant="table-row" :count="max($skeletonCount, 3)" />
                    </tbody>
                </table>
            </div>
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" style="display: none;">
                <x-skeleton variant="card" :count="max($skeletonCount, 3)" />
            </div>
        </div>
        {{-- Real content --}}
        <div x-show="loaded.projects" id="projectsContainer" x-ref="projectsContainer"></div>
    </div>

</div>

<script>
    // ── Alpine Store: shared viewMode accessible by injected fragment JS ────
    document.addEventListener('alpine:init', () => {
        Alpine.store('dashboard', { viewMode: 'table' });
    });

    // Seeded by initProjectSync() once the projects fragment arrives
    let projectStates = {};
    let projectsToSync = [];
    let sortableInstance = null;
    let originalOrderIds = [];

    /**
     * Apply the current view mode to the injected projects fragment.
     * The fragment uses data-view="table|grid" instead of x-show so it
     * doesn't need access to Alpine parent scope.
     */
    function applyViewModeToFragment(mode) {
        const tableEl = document.querySelector('#projectsContainer [data-view="table"]');
        const gridEl  = document.querySelector('#projectsContainer [data-view="grid"]');
        if (tableEl) tableEl.style.display = mode === 'table' ? '' : 'none';
        if (gridEl)  gridEl.style.display  = mode === 'grid'  ? '' : 'none';
    }

    /**
     * (Re)build sync metadata from the JSON data island embedded in the
     * projects fragment. Called after every fragment insertion.
     */
    function initProjectSync() {
        const island = document.getElementById('project-sync-data');
        if (!island) return;
        try {
            const data = JSON.parse(island.textContent);
            projectStates = {};
            projectsToSync = [];
            data.forEach(p => {
                projectStates[p.id] = { status: p.status, syncUrl: p.syncUrl };
                projectsToSync.push({ id: p.id, syncUrl: p.syncUrl });
            });
        } catch (e) {
            console.error('Failed to parse project sync data:', e);
        }
    }

    function initSortable() {
        const grid = document.getElementById('project-cards-grid');
        if (!grid) {
            if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null; }
            return;
        }
        if (sortableInstance) sortableInstance.destroy();

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        sortableInstance = new Sortable(grid, {
            handle: '.drag-handle',
            filter: 'a, button, .project-status-badge',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            animation: prefersReducedMotion ? 0 : 200,
            easing: prefersReducedMotion ? "" : "cubic-bezier(0.16, 1, 0.3, 1)",
            scroll: true, scrollSensitivity: 50, scrollSpeed: 15, bubbleScroll: true,
            delay: 150, delayOnTouchOnly: true,
            onStart(evt) {
                grid.classList.add('sorting-active');
                originalOrderIds = sortableInstance.toArray();
                if (navigator.vibrate) navigator.vibrate(10);
            },
            async onEnd(evt) {
                grid.classList.remove('sorting-active');
                if (evt.item) {
                    evt.item.classList.add('card-settle');
                    setTimeout(() => evt.item.classList.remove('card-settle'), 200);
                }
                const reorderData = Array.from(grid.children)
                    .map((child, index) => ({ id: parseInt(child.getAttribute('data-id')), position: index }))
                    .filter(item => !isNaN(item.id));
                if (!reorderData.length) return;
                try {
                    const response = await fetch('{{ route('projects.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ order: reorderData })
                    });
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();
                    if (!data.success) throw new Error('Server returned failure');
                    if (evt.item) {
                        evt.item.classList.add('card-drop-success');
                        setTimeout(() => evt.item.classList.remove('card-drop-success'), 600);
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Projects order updated successfully.', success: true } }));
                } catch (error) {
                    console.error('Failed to reorder projects:', error);
                    if (originalOrderIds?.length) sortableInstance.sort(originalOrderIds, true);
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Failed to save project positions. Reverted.', success: false } }));
                }
            }
        });
    }

    window.dashboardShell = function dashboardShell() {
        return {
            viewMode: 'table',
            loaded: { stats: false, telemetry: false, projects: false },

            setViewMode(mode) {
                this.viewMode = mode;
                applyViewModeToFragment(mode);
                if (mode === 'grid') Alpine.nextTick(() => initSortable());
            },

            init() {
                const qs = window.location.search;
                this.loadFragment('{{ route('dashboard.fragments.stats') }}' + qs, 'statsContainer', 'stats');
                this.loadFragment('{{ route('dashboard.fragments.telemetry') }}', 'telemetryContainer', 'telemetry');
                this.loadFragment('{{ route('dashboard.fragments.projects') }}' + qs, 'projectsContainer', 'projects', () => {
                    initProjectSync();
                    applyViewModeToFragment(this.viewMode);
                    if (this.viewMode === 'grid') Alpine.nextTick(() => initSortable());
                });
            },

            async loadFragment(url, containerId, key, onDone = null) {
                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const html = await res.text();
                    const container = document.getElementById(containerId);
                    if (container) container.innerHTML = html;
                } catch (e) {
                    console.error('Fragment load failed:', key, e);
                    const container = document.getElementById(containerId);
                    if (container) container.innerHTML =
                        '<div class="p-4 rounded border border-rose-500/20 bg-rose-500/10 text-rose-300 text-xs font-mono">Failed to load ' + key +
                        '. <button class="underline hover:text-rose-300" onclick="window.location.reload()">Reload</button></div>';
                } finally {
                    this.loaded[key] = true;
                    if (onDone) onDone();
                }
            },
        };
    };

    if (typeof Alpine !== 'undefined') {
        Alpine.data('dashboardShell', window.dashboardShell);
    } else {
        document.addEventListener('alpine:init', () => {
            Alpine.data('dashboardShell', window.dashboardShell);
        });
    }

    function getBorderClass(status) {
        switch(status) {
            case 'healthy': return 'border-l-status-healthy-text';
            case 'warning': return 'border-l-status-warning-text';
            case 'critical':
            case 'unreachable': return 'border-l-status-critical-text';
            case 'maintenance': return 'border-l-status-sky-text';
            default: return 'border-l-status-neutral-text';
        }
    }

    function getStatusBadgeHtml(status, reason = null) {
        status = status.toLowerCase();
        let bgClass = '';
        let iconHtml = '';

        switch(status) {
            case 'healthy':
                bgClass = 'bg-status-healthy-bg text-status-healthy-text border-status-healthy-border';
                iconHtml = `<svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
                break;
            case 'warning':
                bgClass = 'bg-status-warning-bg text-status-warning-text border-status-warning-border';
                iconHtml = `<svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>`;
                break;
            case 'critical':
            case 'unreachable':
                bgClass = 'bg-status-critical-bg text-status-critical-text border-status-critical-border';
                iconHtml = `<svg class="w-3 h-3 flex-shrink-0 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
                break;
            case 'maintenance':
                bgClass = 'bg-status-sky-bg text-status-sky-text border-status-sky-border';
                iconHtml = `<svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.075a3 3 0 00-3.484 2.179l-.277 1.15a.75.75 0 01-1.455-.35l.277-1.15A5 5 0 0110 11.66l.755-.756a3.5 3.5 0 114.95 4.95l-.756.755a5 5 0 01-3.535-1.544zm4.95-4.95l-2.5 2.5m-3-3l3-3m-7.208 7.208l2.5-2.5m-3-3l3-3" /></svg>`;
                break;
            default:
                bgClass = 'bg-status-neutral-bg text-status-neutral-text border-status-neutral-border';
                iconHtml = `<svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>`;
                break;
        }

        const hasReason = reason && ['warning', 'unreachable', 'critical'].includes(status);
        let tooltipAttr = '';
        let cursorClass = '';
        if (hasReason) {
            const safeReason = String(reason).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            tooltipAttr = ` data-tooltip="${safeReason}"`;
            cursorClass = ' cursor-help';
        }

        return `<span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded border text-[10px] font-semibold tracking-wider font-mono uppercase ${bgClass}${cursorClass}"${tooltipAttr}>
            ${iconHtml}
            ${status}
        </span>`;
    }

    function updateSyncingState(projectId, isSyncing) {
        const tr = document.getElementById(`project-row-${projectId}`);
        if (tr) {
            if (isSyncing) {
                tr.style.opacity = '0.5';
                tr.style.pointerEvents = 'none';
            } else {
                tr.style.opacity = '1';
                tr.style.pointerEvents = 'auto';
            }
        }

        const card = document.getElementById(`project-card-${projectId}`);
        if (card) {
            if (isSyncing) {
                card.style.opacity = '0.5';
                card.style.pointerEvents = 'none';
            } else {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
        }
    }

    function updateProjectUI(projectId, data) {
        // 1. Update border classes
        const tr = document.getElementById(`project-row-${projectId}`);
        if (tr) {
            tr.classList.remove('border-l-status-healthy-text', 'border-l-status-warning-text', 'border-l-status-critical-text', 'border-l-status-sky-text', 'border-l-status-neutral-text');
            tr.classList.add(getBorderClass(data.status));
        }

        const card = document.getElementById(`project-card-${projectId}`);
        if (card) {
            // border-t status color removed intentionally
        }

        // 2. Update status badges
        const badges = document.querySelectorAll(`.project-status-badge-${projectId}`);
        badges.forEach(badgeContainer => {
            badgeContainer.innerHTML = getStatusBadgeHtml(data.status, data.status_reason);
        });

        // 3. Update table columns
        const traffic = document.getElementById(`project-traffic-${projectId}`);
        if (traffic) traffic.innerText = data.requests_count;

        const errorRate = document.getElementById(`project-error-rate-${projectId}`);
        if (errorRate) {
            errorRate.innerText = data.error_rate;
            if (data.error_rate_raw > 0) {
                errorRate.className = "py-3.5 px-3 text-right font-mono text-status-critical-text font-bold";
            } else {
                errorRate.className = "py-3.5 px-3 text-right font-mono text-zinc-300";
            }
        }

        const latency = document.getElementById(`project-latency-${projectId}`);
        if (latency) latency.innerText = data.avg_response_time_ms;

        const lastSync = document.getElementById(`project-last-sync-${projectId}`);
        if (lastSync) lastSync.innerText = data.checked_at;

        // 4. Update card values
        const cardTraffic = document.getElementById(`project-card-traffic-${projectId}`);
        if (cardTraffic) cardTraffic.innerText = data.requests_count;

        const cardErrorRate = document.getElementById(`project-card-error-rate-${projectId}`);
        if (cardErrorRate) {
            cardErrorRate.innerText = data.error_rate;
            if (data.error_rate_raw > 0) {
                cardErrorRate.className = "mt-1 block text-xs font-bold text-status-critical-text";
            } else {
                cardErrorRate.className = "mt-1 block text-xs font-bold text-zinc-300";
            }
        }

        const cardLatency = document.getElementById(`project-card-latency-${projectId}`);
        if (cardLatency) cardLatency.innerText = data.avg_response_time_ms;

        const cardLastSync = document.getElementById(`project-card-last-sync-${projectId}`);
        if (cardLastSync) cardLastSync.innerText = "Checked " + data.checked_at;

        // 5. Update uptime history bar & percentage dynamically
        if (data.recent_snaps !== undefined) {
            updateUptimeBarUI(projectId, data.uptime_percentage, data.recent_snaps);
        }
    }

    function updateUptimeBarUI(projectId, uptimePct, snaps) {
        const pctText = (uptimePct !== undefined ? uptimePct : 100) + '%';
        let pctColor = 'text-emerald-400';
        if (uptimePct < 90) pctColor = 'text-rose-400';
        else if (uptimePct < 98) pctColor = 'text-amber-400';

        const cardPct = document.getElementById(`project-card-uptime-pct-${projectId}`);
        if (cardPct) {
            cardPct.innerText = pctText;
            cardPct.className = `text-[10px] font-bold ${pctColor}`;
        }
        const tablePct = document.getElementById(`project-table-uptime-pct-${projectId}`);
        if (tablePct) {
            tablePct.innerText = pctText;
            tablePct.className = `text-[10px] font-bold ${pctColor}`;
        }

        if (!snaps || !Array.isArray(snaps)) return;

        const padCount = Math.max(0, 30 - snaps.length);

        const cardBarContainer = document.getElementById(`project-card-uptime-bar-${projectId}`);
        if (cardBarContainer) {
            let html = '';
            for (let i = 0; i < padCount; i++) {
                html += `<span class="flex-1 h-3 rounded-[1px] bg-zinc-800/60" data-tooltip="No telemetry snapshot recorded"></span>`;
            }
            snaps.forEach(s => {
                let bg = 'bg-zinc-700';
                if (s.status === 'healthy') bg = 'bg-emerald-500 hover:bg-emerald-400';
                else if (s.status === 'warning') bg = 'bg-amber-500 hover:bg-amber-400';
                else if (s.status === 'critical' || s.status === 'unreachable') bg = 'bg-rose-500 hover:bg-rose-400';
                else if (s.status === 'maintenance') bg = 'bg-sky-500 hover:bg-sky-400';

                const tooltip = `${s.checked_at} • Status: ${String(s.status).toUpperCase()}` + (s.latency ? ` (${s.latency} ms)` : '');
                html += `<span class="flex-1 h-3 rounded-[1px] ${bg} transition-colors cursor-pointer" data-tooltip="${tooltip}"></span>`;
            });
            cardBarContainer.innerHTML = html;
        }

        const tableBarContainer = document.getElementById(`project-table-uptime-bar-${projectId}`);
        if (tableBarContainer) {
            let html = '';
            for (let i = 0; i < padCount; i++) {
                html += `<span class="w-1.5 h-4 rounded-[1px] bg-zinc-800/60" data-tooltip="No telemetry snapshot recorded"></span>`;
            }
            snaps.forEach(s => {
                let bg = 'bg-zinc-700';
                if (s.status === 'healthy') bg = 'bg-emerald-500 hover:bg-emerald-400';
                else if (s.status === 'warning') bg = 'bg-amber-500 hover:bg-amber-400';
                else if (s.status === 'critical' || s.status === 'unreachable') bg = 'bg-rose-500 hover:bg-rose-400';
                else if (s.status === 'maintenance') bg = 'bg-sky-500 hover:bg-sky-400';

                const tooltip = `${s.checked_at} • Status: ${String(s.status).toUpperCase()}` + (s.latency ? ` (${s.latency} ms)` : '');
                html += `<span class="w-1.5 h-4 rounded-[1px] ${bg} transition-colors cursor-pointer" data-tooltip="${tooltip}"></span>`;
            });
            tableBarContainer.innerHTML = html;
        }
    }

    function updateAggregateWidgets() {
        let healthyCount = 0;
        let warningCount = 0;
        let criticalCount = 0;

        Object.values(projectStates).forEach(proj => {
            if (proj.status === 'healthy') healthyCount++;
            else if (proj.status === 'warning') warningCount++;
            else if (proj.status === 'critical' || proj.status === 'unreachable') criticalCount++;
        });

        const healthySpan = document.getElementById('widget-healthy-count');
        if (healthySpan) {
            healthySpan.innerText = healthyCount;
            const total = Object.keys(projectStates).length;
            const percentageSpan = healthySpan.nextElementSibling;
            if (percentageSpan && total > 0) {
                percentageSpan.innerText = `(${Math.round((healthyCount / total) * 100)}%)`;
            }
        }

        const warningSpan = document.getElementById('widget-warning-count');
        if (warningSpan) warningSpan.innerText = warningCount;

        const criticalSpan = document.getElementById('widget-critical-count');
        if (criticalSpan) criticalSpan.innerText = criticalCount;

        const topAlertBar = document.getElementById('top-alert-bar');
        if (topAlertBar) {
            const checkedText = 'just now';
            if (criticalCount > 0) {
                topAlertBar.innerHTML = `
                    <svg class="h-3.5 w-3.5 text-status-critical-text animate-pulse flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-status-critical-text font-bold uppercase tracking-wider">${criticalCount} services degraded</span>
                    <span class="text-zinc-600">|</span>
                    <span class="text-zinc-500">Last check: ${checkedText}. Action required.</span>
                `;
            } else {
                topAlertBar.innerHTML = `
                    <svg class="h-3.5 w-3.5 text-status-healthy-text flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-status-healthy-text font-bold uppercase tracking-wider">All systems operational</span>
                    <span class="text-zinc-600">|</span>
                    <span class="text-zinc-500">Last check: ${checkedText}. No incidents detected.</span>
                `;
            }
        }
    }

    async function syncProject(projectId, syncUrl) {
        updateSyncingState(projectId, true);

        try {
            const response = await fetch(syncUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Sync failed');

            const data = await response.json();

            if (data.success || data.skipped) {
                // Update local storage status
                if (data.status && projectStates[projectId]) projectStates[projectId].status = data.status;
                // Update UI values
                updateProjectUI(projectId, data);
                // Update aggregates
                updateAggregateWidgets();

                // If jobs were dispatched async, re-fetch after 3 seconds to get fresh data
                if (data.syncing) {
                    setTimeout(() => fetchProjectLatest(projectId, syncUrl), 3000);
                }
            }
        } catch (e) {
            console.error('Error syncing project ' + projectId + ':', e);
        } finally {
            updateSyncingState(projectId, false);
        }
    }

    /**
     * Re-fetch latest data for a project (read-only, no new sync dispatch).
     */
    async function fetchProjectLatest(projectId, syncUrl) {
        try {
            const response = await fetch(syncUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.status && projectStates[projectId]) projectStates[projectId].status = data.status;
            updateProjectUI(projectId, data);
            updateAggregateWidgets();
        } catch (e) {
            // Silent fail on re-fetch
        }
    }

    async function triggerSingleProjectSync(projectId, syncUrl, btnElement) {
        if (!btnElement) return;
        const svg = btnElement.querySelector('svg');
        if (svg) svg.classList.add('animate-spin', 'text-emerald-400');
        btnElement.style.pointerEvents = 'none';

        try {
            await syncProject(projectId, syncUrl);
        } finally {
            if (svg) svg.classList.remove('animate-spin', 'text-emerald-400');
            btnElement.style.pointerEvents = 'auto';
        }
    }

    async function runSyncAllSequence() {
        for (const project of projectsToSync) {
            await syncProject(project.id, project.syncUrl);
        }
    }
    window.runSyncAllSequence = runSyncAllSequence;

    // Auto-refresh polling: silently re-fetch all project data every 30 seconds
    setInterval(async () => {
        for (const project of projectsToSync) {
            try {
                await fetchProjectLatest(project.id, project.syncUrl);
            } catch (e) {
                // Silent fail
            }
        }
    }, 30000);
</script>
@endsection
