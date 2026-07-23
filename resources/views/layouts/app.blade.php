<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Project Monitor') | Telemetry Hub</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v=3">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    
    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- AlpineJS Collapse Plugin (for AI chat tool indicators) -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- Global Alpine components (must run BEFORE alpine) -->
    <script>
        window.sidebarClocks = function sidebarClocks() {
            return {
                utcTime: '',
                asiaTime: '',
                timer: null,

                update() {
                    const now = new Date();
                    this.utcTime = now.toLocaleTimeString('en-GB', {
                        timeZone: 'UTC',
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    }) + ' UTC';

                    this.asiaTime = now.toLocaleTimeString('en-GB', {
                        timeZone: 'Asia/Jakarta',
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    }) + ' WIB';
                },

                start() {
                    this.update();
                    this.timer = setInterval(() => this.update(), 1000);
                }
            };
        };

        document.addEventListener('alpine:init', () => {
            if (typeof Alpine !== 'undefined') {
                Alpine.data('sidebarClocks', window.sidebarClocks);
            }
        });
    </script>

    <!-- Per-page component scripts (must run BEFORE alpine) -->
    @stack('scripts')
    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <!-- ChartJS for Detail View -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: var(--font-sans), sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-display), sans-serif;
            letter-spacing: -0.02em;
        }
        .font-mono {
            font-family: var(--font-mono), monospace;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full flex min-h-screen bg-zinc-950 overflow-hidden antialiased">

    <div class="relative z-10 flex h-full w-full overflow-hidden p-0 md:p-4 md:gap-4" 
         x-data="{ sidebarOpen: window.innerWidth >= 768 ? (localStorage.getItem('sidebarOpen') !== 'false') : false }"
         @keydown.window.ctrl.k.prevent="document.getElementById('project-search')?.focus()"
         @keydown.window.ctrl.m.prevent="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebarOpen', sidebarOpen)">
        
        <!-- MOBILE BACKDROP -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false" 
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden"
             style="display: none;">
        </div>

        <!-- SIDEBAR -->
        <aside class="fixed inset-y-0 left-0 w-64 bg-zinc-950 border-r border-white/10 z-50 md:relative md:inset-auto md:h-full md:z-30 md:bg-zinc-950/40 md:border md:border-white/5 md:rounded-2xl flex flex-col transition-all duration-300 cubic-bezier(0.16, 1, 0.3, 1) font-sans flex-shrink-0"
               :class="sidebarOpen ? 'translate-x-0 opacity-100 scale-100 pointer-events-auto md:w-64 md:opacity-100 md:scale-100' : '-translate-x-full opacity-0 pointer-events-none md:w-0 md:opacity-0 md:scale-95 md:border-0 md:p-0 md:overflow-hidden'">
            <!-- Brand / Logo -->
            <div class="flex h-20 items-center justify-between px-5 border-b border-white/5 bg-zinc-950/60">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3.5 group">
                    <img src="{{ asset('syncops-logo.png') }}?v=3" alt="SyncOps Logo" class="h-16 w-16 object-contain flex-shrink-0">
                    <span class="text-base font-bold tracking-widest text-zinc-100 font-sans group-hover:text-indigo-400 transition">
                        SyncOps
                    </span>
                </a>
                
                <!-- Close Button (Mobile Only) -->
                <button @click="sidebarOpen = false" class="p-1 rounded-md text-zinc-500 hover:text-white hover:bg-white/5 md:hidden transition cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Menu Navigation -->
            <div class="flex-1 flex flex-col py-6 overflow-y-auto px-4">
                <nav class="space-y-6">
                    <div>
                        <span class="px-2 text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Dashboards</span>
                        <div class="mt-2 space-y-1">
                            <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5 px-2.5 py-1.5 text-xs font-semibold rounded-md {{ request()->routeIs('dashboard') ? 'bg-white/5 text-white border-l-2 border-indigo-500' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/2' }} transition-all">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                Systems Monitor
                            </a>
                            <a href="{{ route('logs') }}" class="group flex items-center gap-2.5 px-2.5 py-1.5 text-xs font-semibold rounded-md {{ request()->routeIs('logs') ? 'bg-white/5 text-white border-l-2 border-indigo-500' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/2' }} transition-all">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Telemetry Logs
                            </a>
                            <a href="{{ route('ai.assistant') }}" class="group flex items-center gap-2.5 px-2.5 py-1.5 text-xs font-semibold rounded-md {{ request()->routeIs('ai.assistant') ? 'bg-white/5 text-white border-l-2 border-indigo-500' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/2' }} transition-all">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                                </svg>
                                AI Ops Assistant
                            </a>
                            <a href="{{ route('analytics.index') }}" class="group flex items-center gap-2.5 px-2.5 py-1.5 text-xs font-semibold rounded-md {{ request()->routeIs('analytics.*') ? 'bg-white/5 text-white border-l-2 border-indigo-500' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/2' }} transition-all">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                SLA & Benchmark Suite
                            </a>
                            <a href="{{ route('visitors.index') }}" class="group flex items-center gap-2.5 px-2.5 py-1.5 text-xs font-semibold rounded-md {{ request()->routeIs('visitors.*') ? 'bg-white/5 text-white border-l-2 border-indigo-500' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/2' }} transition-all">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Visitor Analytics
                            </a>
                        </div>
                    </div>

                    <div>
                        <span class="px-2 text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Configuration</span>
                        <div class="mt-2 space-y-1">
                            <a href="#" @click.prevent="$dispatch('open-create-modal')" class="group flex items-center gap-2.5 px-2.5 py-1.5 text-xs font-semibold rounded-md {{ request()->routeIs('projects.create') ? 'bg-white/5 text-white border-l-2 border-indigo-500' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/2' }} transition-all">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Target Project
                            </a>
                        </div>
                    </div>
                <!-- Bottom Docked Widgets Container (Clocks + System Alerts) -->
                <div class="mt-auto pt-4 border-t border-white/5 space-y-3">
                    <!-- Live Clocks Widget -->
                    <div x-data="sidebarClocks()" x-init="start()" class="rounded-lg bg-zinc-900/30 border border-white/5 p-3 space-y-2 font-mono">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-zinc-400 uppercase tracking-wider font-bold flex items-center gap-1.5">
                                <svg class="h-3 w-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                System Clocks
                            </span>
                        </div>

                        <div class="space-y-1.5 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 text-[10px] uppercase tracking-wider">Server (UTC)</span>
                                <span class="font-bold text-indigo-300 font-mono tracking-tight" x-text="utcTime">--:--:--</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 text-[10px] uppercase tracking-wider">Asia (WIB)</span>
                                <span class="font-bold text-emerald-400 font-mono tracking-tight" x-text="asiaTime">--:--:--</span>
                            </div>
                        </div>
                    </div>

                    <!-- System Alerts Widget -->
                    @auth
                        <div class="rounded-lg bg-zinc-900/30 border border-white/5 p-3.5 space-y-2">
                            <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider block">System Alerts</span>
                            @if(isset($topAiAlerts) && $topAiAlerts->isNotEmpty())
                                <div class="space-y-1.5">
                                    @foreach($topAiAlerts as $aiAlert)
                                        <a href="{{ $aiAlert->project ? route('projects.show', $aiAlert->project) : '#' }}"
                                           class="block px-2 py-1.5 rounded bg-white/2 border-l-2 {{ $aiAlert->severity === 'critical' ? 'border-status-critical-text' : 'border-status-warning-text' }} hover:bg-white/5 transition"
                                           title="{{ $aiAlert->priority_reason ?: $aiAlert->content }}">
                                            <span class="block text-[10px] font-semibold text-zinc-300 truncate">
                                                @if($aiAlert->priority_rank)<span class="text-zinc-500 font-mono">#{{ $aiAlert->priority_rank }}</span>@endif
                                                {{ $aiAlert->title }}
                                            </span>
                                            <span class="block text-[9px] text-zinc-500 font-mono truncate">{{ $aiAlert->project?->name }} · {{ $aiAlert->generated_at->diffForHumans(null, true) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <div class="space-y-1.5 font-mono text-[11px]">
                                <div class="flex items-center justify-between text-zinc-400">
                                    <span>Degraded:</span>
                                    <span class="font-bold {{ $globalDegradedCount > 0 ? 'text-status-critical-text' : 'text-zinc-500' }}">
                                        {{ $globalDegradedCount }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-zinc-400">
                                    <span>Warnings:</span>
                                    <span class="font-bold {{ $globalWarningCount > 0 ? 'text-status-warning-text' : 'text-zinc-500' }}">
                                        {{ $globalWarningCount }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-2.5 text-[9px] text-zinc-500 uppercase">
                                    @if($globalDegradedCount > 0)
                                        <svg class="h-3 w-3 text-status-critical-text flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg class="h-3 w-3 text-status-healthy-text flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @endif
                                    {{ $globalDegradedCount > 0 ? "$globalDegradedCount incidents active" : 'all systems operational' }}
                                </div>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </aside>

        <!-- RIGHT MAIN PANELS -->
        <div id="main-panel" class="flex flex-col flex-1 h-full min-w-0 overflow-hidden">
            <div class="flex flex-col flex-1 h-full overflow-hidden bg-zinc-900/10 border border-white/5 rounded-none md:rounded-2xl relative z-10">
                <!-- Breadcrumbs / Top Bar -->
                <header class="flex h-14 items-center justify-between px-6 border-b border-white/5 bg-zinc-950/40 backdrop-blur flex-shrink-0 relative z-20">
                    <div class="flex items-center gap-4 text-xs">
                        <!-- Sidebar Toggle Button -->
                        <button @click="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebarOpen', sidebarOpen)" 
                                class="p-1.5 rounded-lg bg-white/5 border border-white/10 text-zinc-350 hover:text-white hover:bg-white/10 transition-all duration-300 focus:outline-none cursor-pointer"
                                title="Toggle Sidebar (Ctrl+M)">
                            <svg class="h-4 w-4 transition-transform duration-500 cubic-bezier(0.16, 1, 0.3, 1)" :class="{'rotate-180': !sidebarOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="flex items-center gap-2">
                            <span class="text-zinc-500 font-semibold uppercase tracking-wider">Hub</span>
                            <span class="text-zinc-700">|</span>
                            <span class="text-zinc-200 font-mono font-semibold uppercase tracking-wider">@yield('title', 'Dashboard')</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        @auth
                    
                            <div class="flex flex-col items-end">
                                <span class="text-[11px] font-bold text-zinc-200 tracking-tight">Welcome, {{ Auth::user()->name }}</span>
                                <span class="text-[9px] text-zinc-500 uppercase tracking-widest font-mono">{{ Auth::user()->email }}</span>
                            </div>
                            <div class="h-6 w-px bg-white/5"></div>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="p-2 rounded-lg border border-white/10 bg-white/5 text-zinc-400 hover:text-red-400 hover:bg-red-500/10 hover:border-red-500/20 transition duration-150 cursor-pointer"
                                        title="Sign Out">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                    </svg>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded border border-white/10 bg-zinc-900 px-3 py-1 text-[11px] font-semibold text-white hover:bg-zinc-800 transition">
                                Sign In
                            </a>
                        @endauth
                    </div>
                </header>

                <!-- Scrollable Content with Ease-In-Out Page Transition -->
                <main id="main-content" class="flex-1 overflow-y-auto px-6 py-6 bg-transparent relative z-10 page-transition-enter">
                    @yield('content')
                </main>

            </div>
        </div>

    </div>
    <!-- Toast Notifications -->
    <div x-data="{
             show: false,
             message: '',
             isSuccess: true,
             timeout: null,
             showToast(msg, success = true) {
                 this.message = msg;
                 this.isSuccess = success;
                 this.show = true;
                 if (this.timeout) clearTimeout(this.timeout);
                 this.timeout = setTimeout(() => this.show = false, 4000);
             }
         }"
         x-show="show"
         @show-toast.window="showToast($event.detail.message, $event.detail.success)"
         x-init="
             @if(session('success'))
                 showToast('{{ session('success') }}', true);
             @elseif(session('error'))
                 showToast('{{ session('error') }}', false);
             @endif
         "
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50 max-w-sm w-full"
         style="display: none;">
         
        <div class="rounded border border-white/10 bg-zinc-900 p-3.5 shadow-lg flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <template x-if="isSuccess">
                    <div class="rounded-full bg-status-healthy-bg p-0.5 text-status-healthy-text border border-status-healthy-border">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </template>
                <template x-if="!isSuccess">
                    <div class="rounded-full bg-status-critical-bg p-0.5 text-status-critical-text border border-status-critical-border">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </template>
                <p class="text-xs font-semibold text-zinc-200" x-text="message"></p>
            </div>
            <button @click="show = false" class="text-zinc-500 hover:text-zinc-300 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Project Create & Edit Modals -->
    <x-project-modals />

    <!-- Global Confirm Modal -->
    <x-confirm-modal />

    <!-- Global Tooltip Container -->
    <div id="global-tooltip" style="display: none;" class="pointer-events-none transition-opacity duration-150">
        <div id="global-tooltip-content"></div>
        <!-- Arrow Outer (Border) -->
        <div id="global-tooltip-arrow-outer" class="tooltip-arrow"></div>
        <!-- Arrow Inner (Background) -->
        <div id="global-tooltip-arrow-inner" class="tooltip-arrow"></div>
    </div>

    <style>
        #global-tooltip {
            position: fixed;
            z-index: 9999;
            background-color: #18181b; /* zinc-900 */
            color: #d4d4d8; /* zinc-300 */
            font-size: 10px;
            font-family: monospace;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 280px;
            word-break: break-all;
            text-transform: none;
            letter-spacing: normal;
            opacity: 0;
        }
        .tooltip-arrow {
            position: absolute;
            border-width: 4px;
            border-style: solid;
            border-color: transparent;
            left: 50%;
            transform: translateX(-50%);
        }
        .arrow-outer-top {
            top: 100%;
            margin-top: -1px;
            border-top-color: rgba(255, 255, 255, 0.1);
        }
        .arrow-inner-top {
            top: 100%;
            margin-top: -2px;
            border-top-color: #18181b;
        }
        .arrow-outer-bottom {
            bottom: 100%;
            margin-bottom: -1px;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .arrow-inner-bottom {
            bottom: 100%;
            margin-bottom: -2px;
            border-bottom-color: #18181b;
        }
    </style>

    <script>
        const initTooltip = () => {
            const tooltip = document.getElementById('global-tooltip');
            const content = document.getElementById('global-tooltip-content');
            const arrowOuter = document.getElementById('global-tooltip-arrow-outer');
            const arrowInner = document.getElementById('global-tooltip-arrow-inner');
            let hideTimeout = null;

            document.addEventListener('mouseover', (e) => {
                if (!e.target || typeof e.target.closest !== 'function') return;
                const target = e.target.closest('[data-tooltip]');
                if (!target) return;

                const text = target.getAttribute('data-tooltip');
                if (!text) return;

                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }

                content.textContent = text;
                tooltip.style.display = 'block';
                
                // Force a reflow so browser registers display: block before calculation
                tooltip.offsetHeight;
                
                const rect = target.getBoundingClientRect();
                const realWidth = tooltip.offsetWidth;
                const realHeight = tooltip.offsetHeight;
                
                let left = rect.left + (rect.width / 2) - (realWidth / 2);
                if (left < 8) left = 8;
                if (left + realWidth > window.innerWidth - 8) {
                    left = window.innerWidth - realWidth - 8;
                }
                
                let top = rect.top - realHeight - 8;
                
                if (top < 8) {
                    // Position below the element if it goes off screen on top
                    top = rect.bottom + 8;
                    arrowOuter.className = "tooltip-arrow arrow-outer-bottom";
                    arrowInner.className = "tooltip-arrow arrow-inner-bottom";
                } else {
                    // Position above the element
                    arrowOuter.className = "tooltip-arrow arrow-outer-top";
                    arrowInner.className = "tooltip-arrow arrow-inner-top";
                }
                
                tooltip.style.top = `${top}px`;
                tooltip.style.left = `${left}px`;
                tooltip.style.opacity = '1';
            });

            document.addEventListener('mouseout', (e) => {
                if (!e.target || typeof e.target.closest !== 'function') return;
                const target = e.target.closest('[data-tooltip]');
                if (!target) return;
                
                tooltip.style.opacity = '0';
                hideTimeout = setTimeout(() => {
                    tooltip.style.display = 'none';
                }, 150);
            });
        };



        if (document.readyState !== 'loading') {
            initTooltip();
        } else {
            document.addEventListener('DOMContentLoaded', initTooltip);
        }

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => console.log('[Service Worker] Registered with scope:', reg.scope))
                    .catch((err) => console.warn('[Service Worker] Registration failed:', err));
            });
        }

        // Smooth Ease-In-Out Page Transition Handler
        document.addEventListener('DOMContentLoaded', () => {
            const mainContent = document.getElementById('main-content') || document.querySelector('main');
            
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link) return;
                
                const href = link.getAttribute('href');
                const target = link.getAttribute('target');
                
                // Ignore hash links, empty links, javascript:, external tabs, modals, or download links
                if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:') || target === '_blank' || link.hasAttribute('download') || link.getAttribute('onclick')) return;
                if (href === window.location.href) return;
                
                try {
                    const url = new URL(href, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                    
                    e.preventDefault();
                    if (mainContent) {
                        mainContent.classList.remove('page-transition-enter');
                        mainContent.classList.add('page-transition-exit');
                    }
                    
                    setTimeout(() => {
                        window.location.href = url.href;
                    }, 220);
                } catch (err) {
                    // Fallback to normal navigation if URL parsing fails
                }
            });
            
            // Reset transition class if user uses browser Back/Forward buttons (bfcache)
            window.addEventListener('pageshow', (e) => {
                if (e.persisted && mainContent) {
                    mainContent.classList.remove('page-transition-exit');
                    mainContent.classList.add('page-transition-enter');
                }
            });
        });
    </script>
</body>
</html>
