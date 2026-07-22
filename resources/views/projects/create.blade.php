@extends('layouts.app')

@section('title', 'Create Target')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-zinc-500 hover:text-zinc-300 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>
        <h1 class="text-lg font-bold tracking-tight text-white uppercase font-mono tracking-wider">Register Target Service</h1>
    </div>

    <div class="bg-zinc-900/30 border border-white/5 rounded-lg p-6 sm:p-8 shadow-sm">
        <form action="{{ route('projects.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Section 1: General Info -->
            <div class="space-y-4">
                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Service Parameters</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Service Name *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g. API Gateway"
                               class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                        @error('name')
                            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Category</label>
                        <input type="text" name="category" id="category" value="{{ old('category') }}" placeholder="e.g. backend, gateway, client"
                               class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                        @error('category')
                            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Description</label>
                    <textarea name="description" id="description" rows="3" placeholder="Context details or infrastructure path..."
                              class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <hr class="border-white/5">

            <!-- Section 2: Monitoring & URLs -->
            <div class="space-y-4">
                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Uptime Observability</span>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="live_url" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Live URL</label>
                        <input type="url" name="live_url" id="live_url" value="{{ old('live_url') }}" placeholder="https://example.com"
                               class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                        @error('live_url')
                            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="metrics_endpoint" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Metrics Endpoint</label>
                        <input type="url" name="metrics_endpoint" id="metrics_endpoint" value="{{ old('metrics_endpoint') }}" placeholder="https://example.com/metrics"
                               class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-550 focus:outline-none transition font-mono">
                        @error('metrics_endpoint')
                            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <span class="block text-[9px] text-zinc-500 font-mono">If metrics endpoint is left blank, standard latency checks will execute using ping probes on the Live URL.</span>
            </div>

            <hr class="border-white/5">

            <!-- Section 3: GitHub Integration -->
            <div class="space-y-4">
                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Version Control Sync</span>

                <div>
                    <label for="repo_link" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Git Repository Link</label>
                    <input type="text" name="repo_link" id="repo_link" value="{{ old('repo_link') }}" placeholder="e.g. https://github.com/owner/repo"
                           class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                    @error('repo_link')
                        <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <hr class="border-white/5">

            <!-- Section 4: Project Development Status -->
            <div class="space-y-4">
                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Deployment Phase</span>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Stage *</label>
                        <div x-data="{
                                 open: false,
                                 status: '{{ old('status', 'in_progress') }}',
                                 stages: {
                                     'planning': 'Planning',
                                     'in_progress': 'In Progress',
                                     'on_hold': 'On Hold',
                                     'done': 'Done',
                                     'archived': 'Archived'
                                 }
                             }"
                             @click.outside="open = false"
                             class="relative">
                            <input type="hidden" name="status" id="status" :value="status">
                            <button type="button" @click="open = !open"
                                    class="mt-1.5 flex items-center justify-between w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none transition font-mono cursor-pointer">
                                <span x-text="stages[status] || 'In Progress'"></span>
                                <svg class="h-3 w-3 text-zinc-400 transition-transform duration-300 flex-shrink-0" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open"
                                 x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition cubic-bezier(0.16, 1, 0.3, 1) duration-150"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute left-0 right-0 mt-1.5 rounded-xl border border-white/10 bg-zinc-900/95 backdrop-blur-xl p-1 shadow-2xl z-50 overflow-hidden flex flex-col gap-0.5"
                                 style="display: none;">
                                <template x-for="(label, key) in stages" :key="key">
                                    <button type="button" @click="status = key; open = false"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-mono transition cursor-pointer"
                                            :class="status === key ? 'bg-white/10 text-white font-semibold' : 'text-zinc-400 hover:bg-white/5 hover:text-white'">
                                        <span x-text="label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        @error('status')
                            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="progress" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Progress (0-100) *</label>
                        <input type="number" name="progress" id="progress" required min="0" max="100" value="{{ old('progress', 0) }}"
                               class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none transition font-mono">
                        @error('progress')
                            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 font-mono">
                <a href="{{ route('dashboard') }}" class="rounded border border-white/10 bg-zinc-950 px-4 py-2 text-xs font-semibold text-zinc-400 hover:bg-zinc-900 transition">
                    Cancel
                </a>
                <button type="submit" class="rounded border border-white/10 bg-zinc-900 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800 transition">
                    Create Target
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
