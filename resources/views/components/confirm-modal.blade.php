{{--
    Global Confirm Modal — rendered ONCE in layouts/app.blade.php.

    Call from anywhere (Alpine or plain JS):

        confirmAction({
            title: 'Hapus Project?',
            message: 'Project "X" akan dihapus permanen.',
            confirmText: 'Hapus',          // default: 'Confirm'
            cancelText: 'Batal',           // default: 'Cancel'
            confirmStyle: 'danger',        // 'danger' | 'primary' (default: 'danger')
            onConfirm: () => {...},        // may return a Promise → shows loading state
        })
--}}
<div
    x-data="confirmModal()"
    x-on:keydown.escape.window="cancel()"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 flex items-center justify-center p-4 select-none"
    style="display:none; z-index: 9999999;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-modal-title"
>
    {{-- Backdrop with full cross-browser frosted gaussian blur --}}
    <div class="absolute inset-0 bg-black/25"
         style="z-index: 1; -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px);"
         @click="cancel()"></div>

    {{-- Card --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-250 cubic-bezier(0.16, 1, 0.3, 1)"
        x-transition:enter-start="opacity-0 scale-95 translate-y-3"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="relative w-full max-w-md rounded-2xl shadow-2xl overflow-hidden backdrop-blur-xl"
        style="z-index: 2; border: 1px solid rgba(255, 255, 255, 0.12); border-top: none !important; background: rgba(18, 19, 24, 0.96) !important;"
        @click.stop
    >
        <div class="p-6">
            <div class="flex items-start gap-4">
                {{-- Icon Container --}}
                <div class="flex-shrink-0 rounded-xl p-3 border shadow-inner"
                     :class="confirmStyle === 'danger'
                        ? 'bg-red-500/10 border-red-500/20 text-red-400'
                        : 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400'">
                    {{-- Danger warning icon --}}
                    <template x-if="confirmStyle === 'danger'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </template>
                    {{-- Primary info icon --}}
                    <template x-if="confirmStyle !== 'danger'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                        </svg>
                    </template>
                </div>

                <div class="min-w-0 flex-1 pt-0.5">
                    <h3 id="confirm-modal-title" class="text-base font-bold text-white tracking-tight" x-text="title"></h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-400" x-text="message"></p>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-white/5">
                <button type="button"
                        @click="cancel()"
                        :disabled="loading"
                        class="px-4 py-2 text-xs font-semibold text-zinc-400 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition duration-150 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                        x-text="cancelText"></button>

                <button type="button"
                        @click="confirm()"
                        :disabled="loading"
                        x-ref="confirmBtn"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl transition duration-150 shadow-lg disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer border"
                        :class="confirmStyle === 'danger'
                            ? 'bg-red-500/20 border-red-500/30 text-red-300 hover:bg-red-500/30 hover:border-red-500/50 hover:text-white'
                            : 'bg-indigo-500/20 border-indigo-500/30 text-indigo-300 hover:bg-indigo-500/30 hover:border-indigo-500/50 hover:text-white'">
                    <svg x-show="loading" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.confirmModal = function confirmModal() {
        return {
            open: false,
            loading: false,
            title: '',
            message: '',
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            confirmStyle: 'danger',
            onConfirm: null,

            init() {
                window.confirmAction = (opts = {}) => this.show(opts);
                window.addEventListener('confirm-action', (e) => this.show(e.detail || {}));
            },

            show(opts) {
                this.title = opts.title || 'Apakah Anda yakin?';
                this.message = opts.message || '';
                this.confirmText = opts.confirmText || 'Confirm';
                this.cancelText = opts.cancelText || 'Cancel';
                this.confirmStyle = opts.confirmStyle === 'primary' ? 'primary' : 'danger';
                this.onConfirm = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
                this.loading = false;
                this.open = true;

                // Apply smooth Gaussian blur filter to the main application background behind the modal
                const mainApp = document.querySelector('.relative.z-10') || document.querySelector('main');
                if (mainApp) {
                    mainApp.style.transition = 'filter 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
                    mainApp.style.filter = 'blur(10px)';
                }

                this.$nextTick(() => this.$refs.confirmBtn?.focus());
            },

            _removeBlur() {
                const mainApp = document.querySelector('.relative.z-10') || document.querySelector('main');
                if (mainApp) {
                    mainApp.style.filter = '';
                }
            },

            cancel() {
                if (this.loading) return;
                this._removeBlur();
                this.open = false;
                this.onConfirm = null;
            },

            async confirm() {
                if (this.loading) return;
                const cb = this.onConfirm;
                if (!cb) {
                    this._removeBlur();
                    this.open = false;
                    return;
                }

                try {
                    const result = cb();
                    if (result instanceof Promise) {
                        this.loading = true;
                        await result;
                    }
                } catch (e) {
                    console.error('confirmAction onConfirm failed:', e);
                } finally {
                    this._removeBlur();
                    this.loading = false;
                    this.open = false;
                    this.onConfirm = null;
                }
            },
        };
    };

    if (typeof Alpine !== 'undefined') {
        Alpine.data('confirmModal', window.confirmModal);
    } else {
        document.addEventListener('alpine:init', () => {
            Alpine.data('confirmModal', window.confirmModal);
        });
    }
</script>
