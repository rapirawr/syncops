@extends('layouts.app')
@section('title', 'AI Ops Assistant')
@push('styles')
<style>
/* ── AI Chat Layout ─────────────────────────────────────────────────────────── */
/* Override main container: remove padding & scrolling so AI chat fills full height */
#main-content {
    overflow: hidden !important;
    padding: 0 !important;
}
.ai-page-wrapper {
    display: flex;
    flex: 1;
    min-height: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    /* No negative margins needed - padding is removed from #main-content */
}
/* ── Sidebar ─────────────────────────────────────────────────────────────────── */
.ai-sidebar {
    width: 240px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    background: rgba(9,9,11,0.95);
    border-right: 1px solid rgba(255,255,255,0.05);
    overflow: hidden;
    transition: width 0.25s cubic-bezier(0.4,0,0.2,1), opacity 0.2s ease;
}
.ai-sidebar.collapsed {
    width: 0;
}
@media (max-width: 767px) {
    .ai-sidebar {
        position: absolute;
        top: 0; left: 0; bottom: 0;
        z-index: 40;
        width: 260px;
        background: rgba(9,9,11,0.98);
        transform: translateX(-100%);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
        transition: transform 0.25s cubic-bezier(0.4,0,0.2,1);
    }
    .ai-sidebar.open {
        transform: translateX(0);
    }
}
.ai-sidebar-header {
    padding: 0.875rem 0.75rem 0.625rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.ai-new-chat-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6875rem;
    font-weight: 600;
    color: rgba(255,255,255,0.7);
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 0.5rem;
    padding: 0.375rem 0.625rem;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    white-space: nowrap;
}
.ai-new-chat-btn:hover {
    background: rgba(255,255,255,0.12);
    color: #fff;
}
.ai-session-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem 0.375rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.ai-session-group-label {
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.2);
    padding: 0.375rem 0.5rem 0.25rem;
}
.ai-session-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    width: 100%;
    border-radius: 0.5rem;
    padding: 0.375rem 0.5rem;
    cursor: pointer;
    transition: background 0.12s;
    text-align: left;
}
.ai-session-item:hover {
    background: rgba(255,255,255,0.06);
}
.ai-session-item.active {
    background: rgba(99,102,241,0.12);
    border: 1px solid rgba(99,102,241,0.18);
}
.ai-session-item.active .ai-session-title {
    color: rgba(165,180,252,0.9);
}
.ai-session-title {
    font-size: 0.6875rem;
    font-weight: 500;
    color: rgba(255,255,255,0.55);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    min-width: 0;
}
.ai-session-delete {
    opacity: 0;
    padding: 0.125rem;
    border-radius: 0.25rem;
    color: rgba(255,255,255,0.25);
    transition: opacity 0.12s, color 0.12s;
    flex-shrink: 0;
}
.ai-session-item:hover .ai-session-delete {
    opacity: 1;
}
.ai-session-delete:hover {
    color: #f87171;
}
/* ── Main Chat Area ─────────────────────────────────────────────────────────── */
.ai-chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}
.ai-chat-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}
.ai-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1rem 1rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
@media (min-width: 768px) {
    .ai-chat-messages {
        padding: 2rem 2rem 1rem;
    }
}
.ai-chat-messages .ai-chat-turn {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}
.ai-chat-messages .ai-chat-turn.justify-end {
    justify-content: flex-end;
}
.ai-msg-user {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 1rem 1rem 0.25rem 1rem;
    padding: 0.625rem 0.875rem;
    font-size: 0.8125rem;
    color: rgba(255,255,255,0.85);
    max-width: 75%;
    word-break: break-word;
}
.ai-msg-assistant {
    max-width: 100%;
    flex: 1;
    min-width: 0;
}
/* Input area */
.ai-chat-input-area {
    padding: 0 1rem 1rem;
    flex-shrink: 0;
}
@media (min-width: 768px) {
    .ai-chat-input-area {
        padding: 0 2rem 1.25rem;
    }
}
/* Markdown content */
.ai-markdown-body { font-size: 0.8125rem; line-height: 1.65; color: rgba(255,255,255,0.82); }
.ai-markdown-body p { margin-bottom: 0.65em; }
.ai-markdown-body p:last-child { margin-bottom: 0; }
.ai-markdown-body h1,.ai-markdown-body h2,.ai-markdown-body h3 { font-weight: 700; margin: 1em 0 0.4em; color: #fff; }
.ai-markdown-body h1 { font-size: 1.1rem; }
.ai-markdown-body h2 { font-size: 0.95rem; }
.ai-markdown-body h3 { font-size: 0.85rem; }
.ai-markdown-body ul,.ai-markdown-body ol { margin-left: 1.25rem; margin-bottom: 0.65em; }
.ai-markdown-body li { margin-bottom: 0.2em; }
.ai-markdown-body code { font-size: 0.78rem; background: rgba(255,255,255,0.08); border-radius: 0.25rem; padding: 0.1em 0.35em; font-family: var(--font-mono), monospace; }
.ai-markdown-body pre { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; overflow-x: auto; margin: 0.75em 0; }
.ai-markdown-body pre code { background: transparent; padding: 0; font-size: 0.75rem; }
.ai-markdown-body blockquote { border-left: 2px solid rgba(99,102,241,0.5); padding-left: 0.75rem; color: rgba(255,255,255,0.5); margin: 0.5em 0; font-style: italic; }
.ai-markdown-body table { width: 100%; border-collapse: collapse; font-size: 0.78rem; margin: 0.75em 0; }
.ai-markdown-body th { background: rgba(255,255,255,0.06); padding: 0.4em 0.7em; text-align: left; border: 1px solid rgba(255,255,255,0.08); font-weight: 600; color: rgba(255,255,255,0.8); }
.ai-markdown-body td { padding: 0.4em 0.7em; border: 1px solid rgba(255,255,255,0.06); color: rgba(255,255,255,0.65); }
.ai-markdown-body a { color: #818cf8; text-decoration: underline; }
.ai-code-block-wrapper { position: relative; }
.ai-code-copy-btn { position: absolute; top: 0.5rem; right: 0.5rem; font-size: 0.65rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.3rem; padding: 0.2rem 0.5rem; color: rgba(255,255,255,0.5); cursor: pointer; transition: background 0.15s; }
.ai-code-copy-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }
/* Tool indicators */
.ai-tool-indicator { margin-bottom: 0.4rem; background: rgba(6,182,212,0.05); border: 1px solid rgba(6,182,212,0.12); border-radius: 0.5rem; overflow: hidden; transition: border-color 0.2s; }
.ai-tool-indicator.is-done { border-color: rgba(52,211,153,0.15); background: rgba(52,211,153,0.04); }
.ai-tool-header { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0.6rem; cursor: pointer; }
.ai-tool-body { padding: 0 0.6rem 0.6rem; border-top: 1px solid rgba(255,255,255,0.04); }
.ai-thinking-indicator { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; }
.ai-thinking-dots { display: flex; gap: 0.25rem; }
.ai-thinking-dots span { width: 5px; height: 5px; border-radius: 50%; background: rgba(99,102,241,0.7); animation: ai-dot-bounce 1.2s infinite; }
.ai-thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
.ai-thinking-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes ai-dot-bounce { 0%,80%,100% { transform: scale(0.7); opacity: 0.5; } 40% { transform: scale(1); opacity: 1; } }
.ai-error-inline { display: flex; align-items: center; gap: 0.5rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem; padding: 0.5rem 0.75rem; margin-top: 0.375rem; }
.ai-retry-btn { margin-left: auto; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.2rem 0.5rem; border: 1px solid rgba(239,68,68,0.3); border-radius: 0.3rem; color: rgba(252,165,165,0.8); background: rgba(239,68,68,0.08); cursor: pointer; transition: background 0.15s; }
.ai-retry-btn:hover { background: rgba(239,68,68,0.15); }
.ai-msg-actions { display: flex; align-items: center; gap: 0.25rem; margin-top: 0.4rem; }
.ai-action-btn { padding: 0.25rem; border-radius: 0.375rem; color: rgba(255,255,255,0.2); transition: color 0.15s, background 0.15s; }
.ai-action-btn:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.06); }
.ai-content-area { min-height: 1.5rem; }
.ai-chat-form { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 1rem; padding: 0.75rem 0.75rem 0.5rem; }
.ai-chat-input-wrapper { display: flex; flex-direction: column; }
.ai-chat-textarea { background: transparent; border: none; outline: none; color: rgba(255,255,255,0.85); font-size: 0.8125rem; line-height: 1.5; resize: none; width: 100%; min-height: 2rem; max-height: 160px; padding: 0; overflow-y: auto; }
.ai-chat-textarea::placeholder { color: rgba(255,255,255,0.2); }
.ai-send-btn { padding: 0.5rem; background: rgba(99,102,241,0.85); border-radius: 0.5rem; color: #fff; transition: background 0.15s, transform 0.1s; }
.ai-send-btn:hover:not(:disabled) { background: rgba(99,102,241,1); }
.ai-send-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.ai-stop-btn { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.5); background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; padding: 0.375rem 0.75rem; cursor: pointer; transition: background 0.15s; }
.ai-stop-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
</style>
@endpush
@section('content')
<div class="ai-page-wrapper" x-data="aiChat()" x-init="init()">
    {{-- Mobile Backdrop Overlay --}}
    <div x-show="sidebarVisible"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarVisible = false"
         class="md:hidden fixed inset-0 bg-black/75 z-30"
         style="display: none; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);"></div>
    {{-- ═══════ SIDEBAR ═══════ --}}
    <aside class="ai-sidebar" :class="{ 'collapsed': !sidebarVisible, 'open': sidebarVisible }">
        {{-- Header --}}
        <div class="ai-sidebar-header">
            <div class="flex items-center gap-2">
                <button @click="sidebarVisible = false" class="md:hidden p-1 text-white/40 hover:text-white rounded-lg transition" title="Tutup sidebar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <span class="text-[9px] font-bold uppercase tracking-widest text-white/20">Histori</span>
            </div>
            <button @click="newChat()" class="ai-new-chat-btn" title="Obrolan baru (Ctrl+Shift+O)">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Baru
            </button>
        </div>
        {{-- Session list --}}
        <div class="ai-session-list" id="session-list">
            {{-- Skeleton while loading --}}
            <div x-show="sessionsLoading" class="p-3 space-y-2">
                <x-skeleton variant="text" width="w-20" height="h-2.5" class="mb-2" />
                <x-skeleton variant="text" width="w-full" height="h-3.5" count="4" />
            </div>
            {{-- Empty state --}}
            <template x-if="!sessionsLoading && sessions.length === 0">
                <p class="text-[10px] text-white/20 text-center py-6 px-3">Belum ada percakapan.</p>
            </template>
            {{-- Session groups --}}
            <template x-for="(group, label) in groupedSessions" :key="label">
                <div>
                    <div class="ai-session-group-label" x-text="label"></div>
                    <template x-for="s in group" :key="s.id">
                        <div class="ai-session-item" :class="{ 'active': s.id === activeSessionId }"
                             @click="loadSession(s.id)">
                            <span class="ai-session-title" x-text="s.title"></span>
                            <button class="ai-session-delete" @click.stop="deleteSession(s.id)" title="Hapus">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </aside>
    {{-- ═══════ MAIN CHAT ═══════ --}}
    <div class="ai-chat-main transition-all duration-300" :style="sidebarVisible && window.innerWidth < 768 ? 'filter: blur(6px); pointer-events: none;' : ''">
        {{-- Top mini-toolbar for sidebar toggle (chat page only) --}}
        <div class="flex items-center gap-2 px-4 py-2 border-b border-white/5 flex-shrink-0">
            <button @click="sidebarVisible = !sidebarVisible"
                    class="p-1.5 rounded-lg text-white/30 hover:text-white hover:bg-white/5 transition"
                    title="Toggle histori">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
            <span class="text-[10px] text-white/30 font-mono truncate flex-1" x-text="activeSessionTitle || 'AI Ops Assistant'"></span>
        </div>
        <div class="ai-chat-body">
            {{-- Empty State Header + Suggestions --}}
            <div x-show="messages.length === 0" class="flex-1 flex flex-col items-center justify-center py-12 overflow-y-auto">
                @include('ai.chat-empty-state-header')
                @include('ai.chat-empty-state-suggestions', ['suggestedPrompts' => $suggestedPrompts])
            </div>
            {{-- ═══════ MESSAGE LIST ═══════ --}}
            <div x-show="messages.length > 0" x-ref="scrollContainer"
                 @scroll="onScroll()" class="ai-chat-messages flex-1">
                <template x-for="msg in messages" :key="msg.id">
                    <div class="ai-chat-turn" :class="{ 'justify-end': msg.role === 'user' }">
                        {{-- USER MESSAGE --}}
                        <template x-if="msg.role === 'user'">
                            <div class="ai-msg-user">
                                <p x-text="msg.text" class="whitespace-pre-wrap"></p>
                            </div>
                        </template>
                        {{-- ASSISTANT MESSAGE --}}
                        <template x-if="msg.role === 'assistant'">
                            <div class="ai-msg-assistant w-full max-w-none">
                                {{-- Tool Call Indicators --}}
                                <template x-for="(tc, idx) in msg.toolCalls" :key="idx">
                                    <div class="ai-tool-indicator" :class="{ 'is-done': tc.done }">
                                        <button @click="tc.expanded = !tc.expanded" class="ai-tool-header">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <template x-if="!tc.done">
                                                    <svg class="animate-spin h-3.5 w-3.5 text-cyan-400 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </template>
                                                <template x-if="tc.done">
                                                    <svg class="h-3.5 w-3.5 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                    </svg>
                                                </template>
                                                <span class="text-xs font-mono truncate" x-text="tc.done ? 'Used ' + tc.tool : 'Calling ' + tc.tool + '...'"></span>
                                            </div>
                                            <svg class="h-3 w-3 text-white/40 flex-shrink-0 transition-transform duration-200" :class="tc.expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                            </svg>
                                        </button>
                                        <div x-show="tc.expanded && tc.done && tc.result !== null" x-collapse class="ai-tool-body">
                                            <pre class="text-[10px] font-mono text-white/60 whitespace-pre-wrap break-all max-h-48 overflow-y-auto" x-text="JSON.stringify(tc.result, null, 2)"></pre>
                                        </div>
                                    </div>
                                </template>
                                {{-- Thinking State --}}
                                <div x-show="msg.state === 'thinking'" class="ai-thinking-indicator">
                                    <div class="ai-thinking-dots"><span></span><span></span><span></span></div>
                                    <span class="text-xs text-white/50">AI sedang berpikir...</span>
                                </div>
                                {{-- Streaming / Done Content --}}
                                <div x-show="msg.state === 'streaming' || msg.state === 'done' || msg.state === 'error'"
                                     class="ai-content-area">
                                    <template x-if="msg.reasoning">
                                        <details class="mb-4 border border-indigo-500/20 rounded-lg bg-indigo-500/5">
                                            <summary class="cursor-pointer px-3 py-2 text-xs font-semibold text-indigo-400/80 hover:text-indigo-400">Proses Berpikir AI</summary>
                                            <div class="px-3 pb-3 text-xs text-white/60 font-mono whitespace-pre-wrap" x-text="msg.reasoning"></div>
                                        </details>
                                    </template>
                                    <div class="ai-markdown-body" x-html="renderMarkdown(msg.text)"></div>
                                    <div x-show="msg.model_used" class="text-[9px] text-white/20 font-mono mt-1 select-none">
                                        <span x-text="models[msg.model_used]?.label || msg.model_used"></span>
                                    </div>
                                </div>
                                {{-- Error State --}}
                                <div x-show="msg.state === 'error' && msg.errorText" class="ai-error-inline">
                                    <svg class="h-4 w-4 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                    <span class="text-xs text-red-300" x-text="msg.errorText"></span>
                                    <button @click="retry(msg)" class="ai-retry-btn">Coba Lagi</button>
                                </div>
                                {{-- Action Buttons --}}
                                <div x-show="msg.state === 'done'" class="ai-msg-actions" x-transition>
                                    <button @click="copyResponse(msg)" class="ai-action-btn" :title="msg.copied ? 'Tersalin!' : 'Salin jawaban'">
                                        <template x-if="!msg.copied">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/>
                                            </svg>
                                        </template>
                                        <template x-if="msg.copied">
                                            <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </template>
                                    </button>
                                    <button @click="regenerate(msg)" class="ai-action-btn" title="Regenerate">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            {{-- ═══════ INPUT AREA ═══════ --}}
            <div class="ai-chat-input-area">
                {{-- Stop Generating --}}
                <div x-show="busy" x-transition class="flex justify-center pb-2">
                    <button @click="stopGenerating()" class="ai-stop-btn">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z"/>
                        </svg>
                        Stop generating
                    </button>
                </div>
                <form @submit.prevent="send()" class="ai-chat-form">
                    <div class="ai-chat-input-wrapper">
                        <textarea x-model="inputQuestion"
                                  x-ref="chatInput"
                                  @input="autoResize()"
                                  @keydown.enter.prevent="if(!$event.shiftKey) send()"
                                  :disabled="busy || !aiConfigured"
                                  placeholder="Tanyakan sesuatu tentang monitoring proyek Anda..."
                                  rows="1"
                                  class="ai-chat-textarea"></textarea>
                        {{-- Bottom Toolbar --}}
                        <div class="flex items-center justify-between border-t border-white/5 pt-2.5 mt-1 select-none">
                            {{-- Left: mode tabs --}}
                            <div class="flex items-center gap-2">
                                <button type="button" class="p-1.5 text-white/40 hover:text-white bg-white/5 rounded-lg transition hover:bg-white/10" title="Attach context">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </button>
                                <div class="flex items-center gap-1 bg-white/5 p-0.5 rounded-lg border border-white/5">
                                    <span class="bg-white/10 text-white font-medium text-[9px] px-2.5 py-0.5 rounded border border-white/5">Chat</span>
                                </div>
                            </div>
                            {{-- Right: Model selector + Send --}}
                            <div class="flex items-center gap-2">
                                {{-- Model Selector --}}
                                <div class="relative" @click.away="showModelDropdown = false">
                                    <button type="button" @click="showModelDropdown = !showModelDropdown"
                                            class="flex items-center gap-1 text-[9px] text-white/40 bg-white/5 px-2.5 py-1 rounded border border-white/5 hover:bg-white/10 hover:text-white transition select-none">
                                        <span class="font-mono" x-text="models[selectedModel]?.label || selectedModel"></span>
                                        <svg class="w-2.5 h-2.5 text-white/30 transition-transform" :class="showModelDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    {{-- Provider/Model Dropdown --}}
                                    <div x-show="showModelDropdown" x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         class="absolute bottom-full mb-2 right-0 w-44 rounded-xl bg-zinc-900 border border-white/10 shadow-2xl p-1.5 z-50 flex flex-col gap-0.5">
                                        <template x-for="(provData, provKey) in providers" :key="provKey">
                                            <div class="relative">
                                                <button type="button"
                                                        @click="activeGroup = activeGroup === provKey ? null : provKey"
                                                        @mouseenter="if(window.innerWidth >= 640) activeGroup = provKey"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-[10.5px] text-white/70 transition hover:bg-white/5 flex items-center justify-between"
                                                        :class="activeGroup === provKey ? 'bg-white/5 text-white' : ''">
                                                    <svg class="w-2.5 h-2.5 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                                    </svg>
                                                    <span class="font-medium" x-text="provData.label"></span>
                                                </button>
                                                {{-- Submenu --}}
                                                <div x-show="activeGroup === provKey"
                                                     x-transition:enter="transition ease-out duration-100"
                                                     x-transition:enter-start="opacity-0 sm:translate-x-2"
                                                     x-transition:enter-end="opacity-100 sm:translate-x-0"
                                                     class="relative sm:absolute sm:right-full sm:mr-1.5 sm:bottom-0 w-full sm:w-60 rounded-xl bg-black/20 sm:bg-zinc-900 border border-black/10 sm:border-white/10 sm:shadow-2xl p-1 sm:p-1.5 z-50 text-left flex flex-col gap-1 mt-1 sm:mt-0">
                                                    <template x-for="(meta, mKey) in provData.models" :key="mKey">
                                                        <button type="button" @click="selectedModel = mKey; showModelDropdown = false; activeGroup = null"
                                                                class="w-full text-left px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg transition hover:bg-white/5"
                                                                :class="selectedModel === mKey ? 'text-indigo-400 bg-indigo-500/10' : 'text-white/60 hover:text-white/90'">
                                                            <div class="font-semibold text-[10.5px]" x-text="meta.label"></div>
                                                            <div class="text-[9px] text-white/40 mt-0.5 leading-snug" x-text="meta.description"></div>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                {{-- Send Button --}}
                                <button type="submit"
                                        :disabled="busy || !inputQuestion.trim() || !aiConfigured"
                                        class="ai-send-btn">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>{{-- end .ai-chat-input-area --}}
        </div>{{-- end .ai-chat-body --}}
    </div>{{-- end .ai-chat-main --}}
</div>{{-- end .ai-page-wrapper --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
window.aiChat = function aiChat() {
    return {
        inputQuestion: '',
        busy: false,
        messages: [],
        counter: 0,
        aiConfigured: {{ $aiConfigured ? 'true' : 'false' }},
        userAtBottom: true,
        abortController: null,
        selectedModel: @js($selectedModelKey),
        showModelDropdown: false,
        models: @json($modelsList),
        providers: @json($providersList),
        activeGroup: null,
        // Sidebar
        sidebarVisible: window.innerWidth >= 768,
        sessionsLoading: false,
        sessions: @json($sessions),
        activeSessionId: {{ $activeSession ? $activeSession->id : 'null' }},
        activeSessionTitle: @js($activeSession?->title ?? ''),
        get groupedSessions() {
            const groups = {};
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1);
            const week = new Date(today); week.setDate(today.getDate() - 7);
            this.sessions.forEach(s => {
                const d = new Date(s.updated_at);
                let label;
                if (d >= today)         label = 'Hari ini';
                else if (d >= yesterday) label = 'Kemarin';
                else if (d >= week)      label = '7 Hari Terakhir';
                else                     label = 'Lebih Lama';
                if (!groups[label]) groups[label] = [];
                groups[label].push(s);
            });
            return groups;
        },
        init() {
            if (typeof marked !== 'undefined') {
                marked.setOptions({ breaks: true, gfm: true, headerIds: false, mangle: false });
            }
            // Load history if a session is pre-selected from URL
            const history = @json($history);
            if (Array.isArray(history) && history.length > 0) {
                this._loadMessagesFromHistory(history);
                this.$nextTick(() => this.scrollToBottom(true));
            }
            // Keyboard shortcut: Ctrl+Shift+O → new chat
            window.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey && e.key === 'O') { e.preventDefault(); this.newChat(); }
            });
        },
        _loadMessagesFromHistory(history) {
            this.messages = [];
            history.forEach(item => {
                this.messages.push({ id: ++this.counter, role: 'user', text: item.question, state: 'done' });
                if (item.answer) {
                    const tools = Array.isArray(item.tool_calls_used)
                        ? item.tool_calls_used.map(t => ({ tool: t.tool || t, args: t.input || {}, result: null, done: true, expanded: false }))
                        : [];
                    this.messages.push({
                        id: ++this.counter, role: 'assistant',
                        text: item.answer, toolCalls: tools,
                        state: 'done', errorText: '', copied: false,
                        _question: item.question, model_used: item.model_used
                    });
                }
            });
        },
        // ── Sidebar actions ───────────────────────────────────────────────────
        newChat() {
            // Lazy: just reset UI state, NO db call
            this.messages = [];
            this.counter = 0;
            this.activeSessionId = null;
            this.activeSessionTitle = '';
            this.inputQuestion = '';
            window.history.pushState({}, '', '{{ route("ai.assistant") }}');
            if (window.innerWidth < 768) this.sidebarVisible = false;
        },
        async loadSession(id) {
            if (this.activeSessionId === id) {
                if (window.innerWidth < 768) this.sidebarVisible = false;
                return;
            }
            try {
                const res = await fetch(`/api/ai/session/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (!res.ok) { if (res.status === 403) { alert('Akses ditolak.'); } return; }
                const data = await res.json();
                this.activeSessionId = data.session.id;
                this.activeSessionTitle = data.session.title;
                this._loadMessagesFromHistory(data.messages);
                window.history.pushState({}, '', `{{ route("ai.assistant") }}?session=${id}`);
                if (window.innerWidth < 768) this.sidebarVisible = false;
                this.$nextTick(() => this.scrollToBottom(true));
            } catch (e) { console.error(e); }
        },
        deleteSession(id) {
            const s = this.sessions.find(x => x.id === id);
            confirmAction({
                title: 'Hapus Percakapan?',
                message: `Percakapan "${s?.title || 'ini'}" akan dihapus permanen beserta seluruh pesannya.`,
                confirmText: 'Hapus',
                cancelText: 'Batal',
                confirmStyle: 'danger',
                onConfirm: async () => {
                    await fetch(`/api/ai/session/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                    });
                    this.sessions = this.sessions.filter(x => x.id !== id);
                    if (this.activeSessionId === id) this.newChat();
                },
            });
        },
        clearAllHistory() {
            confirmAction({
                title: 'Hapus Semua Riwayat?',
                message: 'SEMUA riwayat percakapan AI akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.',
                confirmText: 'Hapus Semua',
                cancelText: 'Batal',
                confirmStyle: 'danger',
                onConfirm: async () => {
                    await fetch('{{ route("ai.chat.clear") }}', {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                    });
                    this.sessions = [];
                    this.newChat();
                },
            });
        },
        _upsertSessionInSidebar(sessionId, title) {
            const existing = this.sessions.find(s => s.id === sessionId);
            if (existing) {
                existing.title = title;
                // Move to front
                this.sessions = [existing, ...this.sessions.filter(s => s.id !== sessionId)];
            } else {
                this.sessions.unshift({ id: sessionId, title, updated_at: new Date().toISOString() });
            }
        },
        // ── Poll for title update after first message ─────────────────────────
        _pollTitle(sessionId) {
            let attempts = 0;
            const poll = setInterval(async () => {
                attempts++;
                if (attempts > 20) { clearInterval(poll); return; } // max 20s
                try {
                    const res = await fetch(`/api/ai/session/${sessionId}/title`, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    if (!res.ok) { clearInterval(poll); return; }
                    const data = await res.json();
                    if (data.title_generated) {
                        this._upsertSessionInSidebar(sessionId, data.title);
                        if (this.activeSessionId === sessionId) this.activeSessionTitle = data.title;
                        clearInterval(poll);
                    }
                } catch (e) { clearInterval(poll); }
            }, 1000);
        },
        // ── Scroll helpers ────────────────────────────────────────────────────
        onScroll() {
            const el = this.$refs.scrollContainer;
            if (!el) return;
            this.userAtBottom = (el.scrollHeight - el.scrollTop - el.clientHeight) < 80;
        },
        scrollToBottom(force = false) {
            const el = this.$refs.scrollContainer;
            if (!el) return;
            if (force || this.userAtBottom) {
                el.scrollTo({ top: el.scrollHeight, behavior: force ? 'auto' : 'smooth' });
            }
        },
        autoResize() {
            const ta = this.$refs.chatInput;
            if (!ta) return;
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 140) + 'px';
        },
        askPrompt(text) { this.inputQuestion = text; this.send(); },
        stopGenerating() {
            if (this.abortController) { this.abortController.abort(); this.abortController = null; }
            this.busy = false;
            const last = this.messages[this.messages.length - 1];
            if (last && last.role === 'assistant' && last.state !== 'done') last.state = 'done';
        },
        // ── Send ──────────────────────────────────────────────────────────────
        async send() {
            const q = this.inputQuestion.trim();
            if (!q || this.busy) return;
            this.busy = true;
            this.inputQuestion = '';
            this.$nextTick(() => { const ta = this.$refs.chatInput; if (ta) ta.style.height = 'auto'; });
            this.messages.push({ id: ++this.counter, role: 'user', text: q, state: 'done' });
            const assistantMsg = {
                id: ++this.counter, role: 'assistant',
                text: '', reasoning: '', toolCalls: [], state: 'thinking',
                errorText: '', copied: false, _question: q, model_used: this.selectedModel
            };
            this.messages.push(assistantMsg);
            const msgRef = this.messages[this.messages.length - 1];
            this.$nextTick(() => this.scrollToBottom(true));
            this.abortController = new AbortController();
            let isFirstMessageInSession = !this.activeSessionId;
            try {
                const res = await fetch('{{ route("ai.chat.stream") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        question: q,
                        model: this.selectedModel,
                        session_id: this.activeSessionId
                    }),
                    signal: this.abortController.signal
                });
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.error || `HTTP ${res.status}`);
                }
                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();
                    let currentEvent = '';
                    for (const line of lines) {
                        if (line.startsWith('event: ')) {
                            currentEvent = line.slice(7).trim();
                        } else if (line.startsWith('data: ')) {
                            const data = JSON.parse(line.slice(6));
                            this.handleSSE(msgRef, currentEvent, data);
                            currentEvent = '';
                        }
                    }
                    this.$nextTick(() => this.scrollToBottom());
                }
                // Flush remaining buffer
                if (buffer.trim()) {
                    const lines = buffer.split('\n');
                    let currentEvent = '';
                    for (const line of lines) {
                        if (line.startsWith('event: ')) currentEvent = line.slice(7).trim();
                        else if (line.startsWith('data: ')) {
                            try { const data = JSON.parse(line.slice(6)); this.handleSSE(msgRef, currentEvent, data); } catch (e) {}
                            currentEvent = '';
                        }
                    }
                }
                if (msgRef.state !== 'error') msgRef.state = 'done';
                // Poll for generated title if this was the first message
                if (isFirstMessageInSession && this.activeSessionId) {
                    this._pollTitle(this.activeSessionId);
                }
            } catch (err) {
                if (err.name === 'AbortError') {
                    if (!msgRef.text) msgRef.text = '*(Generation stopped)*';
                    msgRef.state = 'done';
                } else {
                    msgRef.state = 'error';
                    msgRef.errorText = err.message || 'Koneksi terputus atau terjadi kesalahan jaringan.';
                }
            } finally {
                this.busy = false;
                this.abortController = null;
                if (msgRef && msgRef.toolCalls) {
                    msgRef.toolCalls.forEach(t => { t.done = true; });
                }
                this.$nextTick(() => this.scrollToBottom());
            }
        },
        // ── SSE handler ───────────────────────────────────────────────────────
        handleSSE(msg, event, data) {
            switch (event) {
                case 'session':
                    // Backend confirmed the session_id; update sidebar
                    this.activeSessionId = data.session_id;
                    this.activeSessionTitle = data.title || 'Percakapan Baru';
                    this._upsertSessionInSidebar(data.session_id, data.title || 'Percakapan Baru');
                    break;
                case 'thinking':
                    msg.state = 'thinking';
                    break;
                case 'tool_start':
                    msg.state = 'streaming';
                    let existingTc = msg.toolCalls.find(t => t.tool === data.tool);
                    if (existingTc) {
                        existingTc.done = false;
                        existingTc.args = data.args || {};
                        existingTc.result = null;
                    } else {
                        msg.toolCalls.push({ tool: data.tool, args: data.args || {}, result: null, done: false, expanded: false });
                    }
                    break;
                case 'tool_end':
                    msg.state = 'streaming';
                    const tc = msg.toolCalls.find(t => t.tool === data.tool);
                    if (tc) {
                        tc.done = true;
                        tc.result = (data.result !== undefined && data.result !== null) ? data.result : {};
                    }
                    break;
                case 'reasoning':
                    if (msg.state !== 'streaming') msg.state = 'streaming';
                    msg.reasoning = (msg.reasoning || '') + data.content;
                    break;
                case 'reasoning_end':
                    break;
                case 'token':
                    if (msg.state !== 'streaming') msg.state = 'streaming';
                    msg.text += data.content;
                    break;
                case 'done':
                    msg.state = 'done';
                    if (data.answer && !msg.text) msg.text = data.answer;
                    msg.toolCalls.forEach(t => { t.done = true; });
                    break;
                case 'error':
                    msg.state = 'error';
                    msg.errorText = data.message || 'Terjadi kesalahan.';
                    break;
            }
        },
        async retry(msg) {
            const question = msg._question;
            if (!question) return;
            const idx = this.messages.indexOf(msg);
            if (idx > -1) this.messages.splice(idx, 1);
            if (idx > 0 && this.messages[idx - 1]?.role === 'user') this.messages.splice(idx - 1, 1);
            this.inputQuestion = question;
            await this.$nextTick();
            this.send();
        },
        async regenerate(msg) {
            const question = msg._question;
            if (!question) return;
            const idx = this.messages.indexOf(msg);
            if (idx > -1) this.messages.splice(idx, 1);
            if (idx > 0 && this.messages[idx - 1]?.role === 'user') this.messages.splice(idx - 1, 1);
            this.inputQuestion = question;
            await this.$nextTick();
            this.send();
        },
        async copyResponse(msg) {
            try {
                await navigator.clipboard.writeText(msg.text);
                msg.copied = true;
                setTimeout(() => { msg.copied = false; }, 2000);
            } catch (e) { console.error('Copy failed', e); }
        },
        renderMarkdown(text) {
            if (!text) return '';
            if (typeof marked !== 'undefined') {
                let prepared = text;
                // Fix missing spaces after list numbers at start of line (e.g. "1.apatkan" -> "1. Dapatkan")
                prepared = prepared.replace(/^(\d+\.)([A-Za-z])/gm, '$1 $2');
                // Fix list items merged into previous text without newline (e.g. "Analytics2. Salin" -> "Analytics\n\n2. Salin")
                prepared = prepared.replace(/([^\n\d])(\d+\.\s+[A-Z])/g, '$1\n\n$2');
                // Ensure empty line before tables
                prepared = prepared.replace(/([^\n])\n(\|[^\n]+\|)/g, '$1\n\n$2');
                // Ensure empty line before code blocks
                prepared = prepared.replace(/([^\n])\n(```[a-z]*)/g, '$1\n\n$2');

                let html = marked.parse(prepared);
                html = html.replace(/<pre><code(.*?)>([\s\S]*?)<\/code><\/pre>/g, (match, attrs, code) => {
                    return `<div class="ai-code-block-wrapper"><button class="ai-code-copy-btn" onclick="navigator.clipboard.writeText(this.parentElement.querySelector('code').textContent).then(()=>{this.textContent='✓ Copied';setTimeout(()=>{this.textContent='Copy'},1500)})">Copy</button><pre><code${attrs}>${code}</code></pre></div>`;
                });
                return html;
            }
            return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        }
    };
};

if (typeof Alpine !== 'undefined') {
    Alpine.data('aiChat', window.aiChat);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('aiChat', window.aiChat);
    });
}
</script>
@endpush