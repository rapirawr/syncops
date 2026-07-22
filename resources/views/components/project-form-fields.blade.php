@props(['project' => null])

<!-- Section 1: General Info -->
<div class="space-y-4">
    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Service Parameters</span>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="name" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Service Name *</label>
            <input type="text" name="name" id="name" required value="{{ old('name', $project?->name) }}" placeholder="e.g. API Gateway"
                   class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
            @error('name')
                <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="category" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Category</label>
            <input type="text" name="category" id="category" value="{{ old('category', $project?->category) }}" placeholder="e.g. saas, backend"
                   class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
            @error('category')
                <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="description" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Description</label>
        <textarea name="description" id="description" rows="3" placeholder="Context details..."
                  class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">{{ old('description', $project?->description) }}</textarea>
        @error('description')
            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
        @enderror
    </div>
</div>

<hr class="border-white/5">

<!-- Section 2: Uptime Observability -->
<div class="space-y-4">
    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Uptime Observability</span>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="live_url" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Live URL</label>
            <input type="url" name="live_url" id="live_url" value="{{ old('live_url', $project?->live_url) }}" placeholder="https://example.com"
                   class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
            @error('live_url')
                <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="metrics_endpoint" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Metrics Endpoint</label>
            <input type="url" name="metrics_endpoint" id="metrics_endpoint" value="{{ old('metrics_endpoint', $project?->metrics_endpoint) }}" placeholder="https://example.com/metrics"
                   class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-550 focus:outline-none transition font-mono">
            @error('metrics_endpoint')
                <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

<hr class="border-white/5">

<!-- Section 3: Version Control Sync -->
<div class="space-y-4">
    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Version Control Sync</span>

    <div>
        <label for="repo_link" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Git Repository Link</label>
        <input type="text" name="repo_link" id="repo_link" value="{{ old('repo_link', (!empty($project?->repo_owner) && !empty($project?->repo_name)) ? 'https://github.com/' . $project->repo_owner . '/' . $project->repo_name : '') }}" placeholder="e.g. https://github.com/owner/repo"
               class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
        @error('repo_link')
            <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
        @enderror
    </div>
</div>

<hr class="border-white/5">

<!-- Section 4: Deployment Phase -->
<div class="space-y-4">
    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">Deployment Phase</span>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Stage *</label>
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
                                <input type="hidden" name="status" id="create_status" :value="status">
                                <button type="button" @click="open = !open"
                                         class="flex items-center justify-between gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] text-zinc-200 hover:text-white transition font-mono min-w-[140px] focus:outline-none cursor-pointer">
                                    <span x-text="stages[status] || 'In Progress'"></span>
                                    <svg class="h-3 w-3 text-zinc-400 transition-transform duration-300 flex-shrink-0" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>
    <div>
    <label for="progress" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Progress (0-100) *</label>
     <input type="number" name="progress" id="progress" required min="0" max="100" value="{{ old('progress', $project?->progress ?? 0) }}"
                   class="mt-1.5 block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none transition font-mono">
            @error('progress')
                <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
