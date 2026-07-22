{{--
    Project Create + Edit Modals (Minimalist Version)
    Triggered via Alpine.js: $dispatch('open-create-modal') / $dispatch('open-edit-modal', { url: '...' })
--}}
<div
    x-data="projectModals()"
    @open-create-modal.window="openCreate()"
    @open-edit-modal.window="openEdit($event.detail.url)"
    @keydown.escape.window="closeAll()"
>

    {{-- ── CREATE MODAL ─────────────────────────────────── --}}
    <div
        x-show="showCreate"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none"
    >
        {{-- Backdrop with frosted gaussian blur (light backdrop tint, high blur) --}}
        <div class="absolute inset-0 bg-black/20" style="-webkit-backdrop-filter: blur(24px); backdrop-filter: blur(24px);" @click="closeAll()"></div>

        {{-- Panel --}}
        <div
            x-show="showCreate"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-h-[90vh] overflow-y-auto border rounded-2xl shadow-2xl z-10 backdrop-blur-xl"
            style="max-width: 460px; background: rgba(18, 19, 24, 0.96) !important; border-color: rgba(255, 255, 255, 0.12) !important;"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/5 sticky top-0 z-10" style="background: rgba(18, 19, 24, 0.98) !important;">
                <span class="text-xs font-bold text-white uppercase tracking-widest font-mono">Register Target Service</span>
                <button @click="closeAll()" class="text-zinc-550 hover:text-zinc-300 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form action="{{ route('projects.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                
                {{-- Form Fields --}}
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="create_name" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Service Name *</label>
                            <input type="text" name="name" id="create_name" required value="{{ old('name') }}" placeholder="e.g. API Gateway"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                            @error('name')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="create_category" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Category</label>
                            <input type="text" name="category" id="create_category" value="{{ old('category') }}" placeholder="e.g. backend"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                            @error('category')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono mb-1">Stage *</label>
                            <x-dropdown 
                                name="status" 
                                id="create_status" 
                                :selected="old('status', 'in_progress')" 
                                :options="[
                                    'planning' => 'Planning',
                                    'in_progress' => 'In Progress',
                                    'on_hold' => 'On Hold',
                                    'done' => 'Done',
                                    'archived' => 'Archived'
                                ]" 
                                class="w-full mt-1"
                                button-class="w-full"
                            />
                            @error('status')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="create_progress" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Progress (0-100) *</label>
                            <input type="number" name="progress" id="create_progress" required min="0" max="100" value="{{ old('progress', 0) }}"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none transition font-mono">
                            @error('progress')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Optional & Configuration Fields --}}
                    <div class="space-y-4 pt-4 border-t border-white/5">
                        <div>
                            <label for="create_description" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Description</label>
                            <textarea name="description" id="create_description" rows="2" placeholder="Context details..."
                                      class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="create_live_url" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Live URL</label>
                                <input type="url" name="live_url" id="create_live_url" value="{{ old('live_url') }}" placeholder="https://example.com"
                                       class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                                @error('live_url')
                                    <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="create_metrics_endpoint" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Metrics Endpoint</label>
                                <input type="url" name="metrics_endpoint" id="create_metrics_endpoint" value="{{ old('metrics_endpoint') }}" placeholder="https://example.com/metrics"
                                       class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                                @error('metrics_endpoint')
                                    <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="create_timeout_seconds" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Sync Timeout (Seconds)</label>
                            <input type="number" name="timeout_seconds" id="create_timeout_seconds" min="1" max="60" value="{{ old('timeout_seconds', 5) }}" placeholder="5"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                            @error('timeout_seconds')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="create_repo_link" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Git Repository Link</label>
                            <input type="text" name="repo_link" id="create_repo_link" value="{{ old('repo_link') }}" placeholder="e.g. https://github.com/owner/repo"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                            @error('repo_link')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-3 flex items-center justify-end gap-2.5 font-mono border-t border-white/5">
                    <button type="button" @click="closeAll()" class="rounded border border-white/10 bg-zinc-950 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-400 hover:bg-zinc-900 transition">Cancel</button>
                    <button type="submit" class="rounded border border-indigo-500/30 bg-indigo-500/10 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-indigo-300 hover:bg-indigo-500/20 transition">Create Target</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── EDIT MODAL ──────────────────────────────────── --}}
    <div
        x-show="showEdit"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none"
    >
        <div class="absolute inset-0 bg-black/20" style="-webkit-backdrop-filter: blur(24px); backdrop-filter: blur(24px);" @click="closeAll()"></div>

        <div
            x-show="showEdit"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-h-[90vh] overflow-y-auto border rounded-2xl shadow-2xl z-10 backdrop-blur-xl"
            style="max-width: 460px; background: rgba(18, 19, 24, 0.96) !important; border-color: rgba(255, 255, 255, 0.12) !important;"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/5 sticky top-0 z-10" style="background: rgba(18, 19, 24, 0.98) !important;">
                <span class="text-xs font-bold text-white uppercase tracking-widest font-mono" x-text="'Modify: ' + (editData.name || '...')"></span>
                <button @click="closeAll()" class="text-zinc-550 hover:text-zinc-300 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Loading state --}}
            <div x-show="editLoading" class="flex items-center justify-center py-16 text-zinc-550 font-mono text-xs">
                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Loading...
            </div>

            {{-- Form --}}
            <form x-show="!editLoading" :action="editData.update_url" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="id" :value="editData.id">

                {{-- Form Fields --}}
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Service Name *</label>
                            <input type="text" name="name" required :value="editData.name"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Category</label>
                            <input type="text" name="category" :value="editData.category" placeholder="e.g. backend"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Stage *</label>
                            <div x-data="{
                                     open: false,
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
                                <input type="hidden" name="status" :value="editData.status">
                                <button type="button" @click="open = !open"
                                        class="mt-1 flex items-center justify-between w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none transition font-mono cursor-pointer">
                                    <span x-text="stages[editData.status] || 'In Progress'"></span>
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
                                     class="absolute left-0 right-0 mt-1.5 rounded-xl border border-white/15 bg-zinc-950/98 backdrop-blur-2xl p-1 shadow-2xl z-50 overflow-hidden flex flex-col gap-0.5"
                                     style="display: none; -webkit-backdrop-filter: blur(20px); backdrop-filter: blur(20px);">
                                    <template x-for="(label, key) in stages" :key="key">
                                        <button type="button" @click="editData.status = key; open = false"
                                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-mono transition cursor-pointer"
                                                :class="editData.status === key ? 'bg-white/10 text-white font-semibold' : 'text-zinc-400 hover:bg-white/5 hover:text-white'">
                                            <span x-text="label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Progress (0-100) *</label>
                            <input type="number" name="progress" required min="0" max="100" :value="editData.progress"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none transition font-mono">
                        </div>
                    </div>

                    {{-- Optional & Configuration Fields --}}
                    <div class="space-y-4 pt-4 border-t border-white/5">
                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Description</label>
                            <textarea name="description" rows="2" :value="editData.description" placeholder="Context details..."
                                      class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Live URL</label>
                                <input type="url" name="live_url" :value="editData.live_url" placeholder="https://example.com"
                                       class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Metrics Endpoint</label>
                                <input type="url" name="metrics_endpoint" :value="editData.metrics_endpoint" placeholder="https://example.com/metrics"
                                       class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Sync Timeout (Seconds)</label>
                            <input type="number" name="timeout_seconds" min="1" max="60" :value="editData.timeout_seconds || 5" placeholder="5"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                        </div>

                        <div>
                            <label for="edit_repo_link" class="block text-[9px] font-bold uppercase tracking-wider text-zinc-500 font-mono">Git Repository Link</label>
                            <input type="text" name="repo_link" id="edit_repo_link" :value="editData.repo_link" placeholder="e.g. https://github.com/owner/repo"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                            @error('repo_link')
                                <p class="mt-1 text-[9px] font-mono text-status-critical-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Change Note (Optional)</label>
                            <input type="text" name="status_note" placeholder="e.g. Migration complete"
                                   class="mt-1 block w-full rounded border border-white/10 bg-zinc-900 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-3 flex items-center justify-between gap-2.5 font-mono border-t border-white/5">
                    <button type="button" @click="confirmAction({
                        title: 'Hapus Project?',
                        message: `Project '${editData.name || 'ini'}' akan dihapus permanen beserta semua histori metrics-nya.`,
                        confirmText: 'Hapus',
                        cancelText: 'Batal',
                        confirmStyle: 'danger',
                        onConfirm: () => document.getElementById('delete-project-form').submit(),
                    })" title="Hapus Project" class="rounded-xl border border-red-500/30 bg-red-500/10 p-2 text-red-400 hover:bg-red-500/20 hover:text-red-300 transition cursor-pointer shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeAll()" class="rounded border border-white/10 bg-zinc-950 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-400 hover:bg-zinc-900 transition">Cancel</button>
                        <button type="submit" class="rounded border border-indigo-500/30 bg-indigo-500/10 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-indigo-300 hover:bg-indigo-500/20 transition">Save Changes</button>
                    </div>
                </div>
            </form>

            {{-- Delete form --}}
            <form id="delete-project-form" :action="editData.destroy_url" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
            </form>
        </div>
    </div>

</div>

<script>
window.projectModals = function projectModals() {
    return {
        showCreate: {{ (session('open_create_modal') || ($errors->any() && !old('_method'))) ? 'true' : 'false' }},
        showEdit:   {{ (session('open_edit_modal') || ($errors->any() && old('_method') === 'PUT')) ? 'true' : 'false' }},
        editLoading: false,
        editData: {
            @if($errors->any() && old('_method') === 'PUT')
                id: '{{ old('id') }}',
                name: '{{ old('name') }}',
                category: '{{ old('category') }}',
                description: '{{ old('description') }}',
                live_url: '{{ old('live_url') }}',
                metrics_endpoint: '{{ old('metrics_endpoint') }}',
                timeout_seconds: '{{ old('timeout_seconds', 5) }}',
                repo_link: '{{ old('repo_link') }}',
                status: '{{ old('status') }}',
                progress: '{{ old('progress') }}',
                update_url: '{{ route('projects.update', old('id', 0)) }}',
                destroy_url: '{{ route('projects.destroy', old('id', 0)) }}'
            @else
                name: '',
                category: '',
                description: '',
                live_url: '',
                metrics_endpoint: '',
                timeout_seconds: 5,
                repo_link: '',
                status: 'in_progress',
                progress: 0,
                update_url: '#',
                destroy_url: '#'
            @endif
        },

        init() {
            if (this.showCreate || this.showEdit) {
                document.body.style.overflow = 'hidden';
                this._applyBlur();
            }
            @if(session('open_edit_modal'))
                this.openEdit('{{ route('projects.edit-data', session('open_edit_modal')) }}');
            @endif
        },

        _applyBlur() {
            const mainApp = document.querySelector('.relative.z-10') || document.querySelector('main');
            if (mainApp) {
                mainApp.style.transition = 'filter 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
                mainApp.style.filter = 'blur(10px)';
            }
        },

        _removeBlur() {
            const mainApp = document.querySelector('.relative.z-10') || document.querySelector('main');
            if (mainApp) {
                mainApp.style.filter = '';
            }
        },

        openCreate() {
            this.showEdit   = false;
            this.showCreate = true;
            document.body.style.overflow = 'hidden';
            this._applyBlur();
        },

        async openEdit(url) {
            this.showCreate = false;
            this.editData   = {};
            this.editLoading = true;
            this.showEdit   = true;
            document.body.style.overflow = 'hidden';
            this._applyBlur();

            try {
                const res  = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                this.editData = await res.json();

                // Sync select value after data loads
                await this.$nextTick();
                const sel = this.$el.querySelector('select[name="status"]');
                if (sel && this.editData.status) sel.value = this.editData.status;
            } catch(e) {
                console.error('Failed to load project data', e);
            } finally {
                this.editLoading = false;
            }
        },

        closeAll() {
            this.showCreate = false;
            this.showEdit   = false;
            document.body.style.overflow = '';
            this._removeBlur();
        }
    };
};

if (typeof Alpine !== 'undefined') {
    Alpine.data('projectModals', window.projectModals);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('projectModals', window.projectModals);
    });
}
</script>
