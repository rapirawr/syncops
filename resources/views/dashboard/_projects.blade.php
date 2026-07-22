{{-- Fragment: project table + card grid. Rendered by DashboardController@fragmentProjects.
     Includes a JSON data island consumed by initProjectSync() in dashboard.blade.php --}}

@if($projects->isEmpty())
    <div class="rounded-lg border border-white/5 bg-zinc-900/10 p-12 text-center font-mono">
        <h3 class="text-xs font-bold text-zinc-300 uppercase tracking-wider">No systems under observation</h3>
        <p class="mt-1 text-[11px] text-zinc-500">Initialize a telemetry scanner to view status snapshot.</p>
        <div class="mt-6">
            <button type="button" @click.prevent="$dispatch('open-create-modal')" class="inline-flex items-center gap-1.5 rounded border border-white/10 bg-zinc-900 px-4 py-2 text-[11px] font-semibold text-white hover:bg-zinc-800 transition">
                Add Target Project
            </button>
        </div>
    </div>
@else

    <style>
        /* Drag & Drop Visuals */
        .sortable-ghost {
            border: 2px dashed rgba(99, 102, 241, 0.4) !important;
            background-color: rgba(255, 255, 255, 0.05) !important; /* bg-white/5 */
            border-radius: 16px !important; /* matches card rounded-lg */
            box-shadow: none !important;
            position: relative;
        }

        /* Hide all contents inside the ghost to keep placeholder clean while preserving layout height */
        .sortable-ghost * {
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Overlay pulsing animation on the ghost element */
        .sortable-ghost::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 16px;
            border: 2px solid rgba(99, 102, 241, 0.25);
            animation: ghostPulse 1.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            pointer-events: none;
        }

        @keyframes ghostPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.8; }
        }

        /* Card active dragging style (the floating clone card under cursor) */
        .sortable-drag {
            opacity: 0.8 !important;
            transform: scale(1.05) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important; /* shadow-2xl */
            border-color: rgba(99, 102, 241, 0.4) !important;
            background-color: rgba(24, 24, 27, 0.85) !important; /* zinc-900 with glass opacity */
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            cursor: grabbing !important;
        }

        /* Settle card drop bounce */
        @keyframes settleBounce {
            0% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        .card-settle {
            animation: settleBounce 200ms cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards !important;
            transition: none !important;
        }

        /* Settle card drop success flash */
        @keyframes dropSuccessFlash {
            0% {
                box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.8);
                border-color: rgba(99, 102, 241, 1) !important;
            }
            100% {
                box-shadow: none;
            }
        }
        .card-drop-success {
            animation: dropSuccessFlash 600ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* During sorting, set grabbing cursor on the body */
        .sorting-active, .sorting-active * {
            cursor: grabbing !important;
        }

        .sorting-active .group {
            /* Disable CSS transitions on transform during drag so it doesn't fight SortableJS */
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, filter, backdrop-filter !important;
        }

        .drag-handle {
            cursor: grab;
            touch-action: none;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        /* Respect prefers-reduced-motion media query */
        @media (prefers-reduced-motion: reduce) {
            .sortable-drag {
                transform: none !important;
                opacity: 0.8 !important;
                transition: opacity 200ms ease !important;
            }
            .card-settle {
                animation: none !important;
            }
            .sortable-ghost::after {
                animation: none !important;
            }
            .card-drop-success {
                animation: none !important;
            }
        }
    </style>

    <!-- 1. TABLE VIEW (Default) -->
    <div data-view="table" class="overflow-x-auto bg-zinc-900/20 border border-white/5 rounded-lg shadow-sm">
        <table class="min-w-full divide-y divide-white/5 text-left text-xs align-middle">
            <thead class="bg-zinc-950/80 sticky top-0 uppercase tracking-widest text-[9px] font-bold text-zinc-500 font-mono">
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
            <tbody class="divide-y divide-white/5 font-sans">
                @foreach($projects as $project)
                    @php
                        $runtimeStatus = $project->runtime_status;
                        $latestMetric = $project->latestMetricsSnapshot;
                        $statusBorder = match($runtimeStatus) {
                            'healthy' => 'border-l-status-healthy-text',
                            'warning' => 'border-l-status-warning-text',
                            'critical', 'unreachable' => 'border-l-status-critical-text',
                            'maintenance' => 'border-l-status-sky-text',
                            default => 'border-l-status-neutral-text',
                        };
                    @endphp
                    <tr id="project-row-{{ $project->id }}" class="hover:bg-white/2 transition duration-150">
                        <!-- Status Badging -->
                        <td class="py-3.5 pl-4 pr-3 whitespace-nowrap">
                            <div class="project-status-badge-{{ $project->id }}">
                                <x-status-badge :status="$runtimeStatus" :reason="$project->runtime_status_reason" />
                            </div>
                        </td>
                        <!-- Service Name -->
                        <td class="py-3.5 px-3 font-semibold text-zinc-200">
                            <a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-400 transition-colors">
                                {{ $project->name }}
                            </a>
                            <span class="block text-[10px] text-zinc-550 font-normal font-mono">{{ $project->slug }}</span>
                        </td>
                        <!-- Category & Dev Stage -->
                        <td class="py-3.5 px-3 font-mono text-[10px] text-zinc-500 uppercase">
                            {{ $project->category ?: 'neutral' }}
                            <span class="mx-1 text-zinc-700">/</span>
                            <span class="text-zinc-400 capitalize">{{ str_replace('_', ' ', $project->status) }}</span>
                        </td>
                        <!-- Uptime History Timeline (30 Checks) -->
                        <td class="py-3.5 px-3 text-center whitespace-nowrap font-mono">
                            @php
                                $recentSnaps = $project->recentMetricsSnapshots->reverse();
                                $padCount = max(0, 30 - $recentSnaps->count());
                            @endphp
                            <div class="inline-flex items-center gap-2">
                                <div class="flex items-center gap-[2px]">
                                    @for($i = 0; $i < $padCount; $i++)
                                        <span class="w-1.5 h-4 rounded-[1px] bg-zinc-800/60" data-tooltip="No telemetry snapshot recorded"></span>
                                    @endfor
                                    @foreach($recentSnaps as $snap)
                                        @php
                                            $barBg = match($snap->health_status) {
                                                'healthy' => 'bg-emerald-500 hover:bg-emerald-400',
                                                'warning' => 'bg-amber-500 hover:bg-amber-400',
                                                'critical', 'unreachable' => 'bg-rose-500 hover:bg-rose-400',
                                                'maintenance' => 'bg-sky-500 hover:bg-sky-400',
                                                default => 'bg-zinc-700',
                                            };
                                            $tooltipStr = ($snap->checked_at ? $snap->checked_at->format('M d, H:i') : 'Ping Scan') . ' • Status: ' . strtoupper($snap->health_status) . ($snap->avg_response_time_ms ? ' (' . $snap->avg_response_time_ms . ' ms)' : '');
                                        @endphp
                                        <span class="w-1.5 h-4 rounded-[1px] {{ $barBg }} transition-colors cursor-pointer" data-tooltip="{{ $tooltipStr }}"></span>
                                    @endforeach
                                </div>
                                <span class="text-[10px] font-bold {{ ($project->uptime_percentage ?? 100) >= 98 ? 'text-emerald-400' : (($project->uptime_percentage ?? 100) >= 90 ? 'text-amber-400' : 'text-rose-400') }}">
                                    {{ $project->uptime_percentage ?? 100 }}%
                                </span>
                            </div>
                        </td>
                        <!-- IP Address -->
                        <td class="py-3.5 px-3 font-mono text-[10px] text-zinc-400 whitespace-nowrap">
                            @if($project->ip_address)
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500/60 flex-shrink-0"></span>
                                    {{ $project->ip_address }}
                                </span>
                            @else
                                <span class="text-zinc-600 italic">—</span>
                            @endif
                        </td>
                        <!-- Requests Count -->
                        <td id="project-traffic-{{ $project->id }}" class="py-3.5 px-3 text-right font-mono text-zinc-300">
                            {{ $latestMetric ? number_format($latestMetric->requests_count) : '—' }}
                        </td>
                        <!-- Error rate -->
                        <td id="project-error-rate-{{ $project->id }}" class="py-3.5 px-3 text-right font-mono {{ $latestMetric && $latestMetric->error_rate > 0 ? 'text-status-critical-text font-bold' : 'text-zinc-300' }}">
                            {{ $latestMetric ? number_format($latestMetric->error_rate, 1) . '%' : '—' }}
                        </td>
                        <!-- Latency -->
                        <td id="project-latency-{{ $project->id }}" class="py-3.5 px-3 text-right font-mono text-zinc-300">
                            {{ $latestMetric && $latestMetric->avg_response_time_ms ? $latestMetric->avg_response_time_ms . ' ms' : '—' }}
                        </td>
                        <!-- Last sync timing -->
                        <td id="project-last-sync-{{ $project->id }}" class="py-3.5 px-3 text-right font-mono text-[10px] text-zinc-500">
                            {{ $latestMetric && $latestMetric->checked_at ? $latestMetric->checked_at->diffForHumans() : 'never' }}
                        </td>
                        <!-- Tech Stack -->
                        <td class="py-3.5 px-3">
                            @if($project->latestGithubSnapshot && is_array($project->latestGithubSnapshot->detected_technologies) && count($project->latestGithubSnapshot->detected_technologies) > 0)
                                <x-tech-badges :techs="$project->latestGithubSnapshot->detected_technologies" :limit="3" />
                            @else
                                <span class="text-zinc-700 text-[10px] font-mono italic">—</span>
                            @endif
                        </td>
                        <!-- Quick View Details & Sync Actions -->
                        <td class="py-3.5 pr-6 pl-3 text-right whitespace-nowrap space-x-3">
                            <button type="button"
                                    onclick="triggerSingleProjectSync({{ $project->id }}, '{{ route('projects.sync', $project) }}', this)"
                                    class="text-zinc-400 hover:text-emerald-400 transition-colors inline-flex items-center align-middle cursor-pointer"
                                    title="{{ $project->status === 'done' ? 'Sync Project Telemetry' : 'Sync Skipped: Development Stage is ' . str_replace('_', ' ', $project->status) }}">
                                <svg class="h-3.5 w-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </button>
                            <button type="button" @click.prevent="$dispatch('open-edit-modal', { url: '{{ route('projects.edit-data', $project) }}' })" class="text-zinc-400 hover:text-zinc-200 transition-colors inline-flex items-center align-middle cursor-pointer" title="Edit Project">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 17.017a4.5 4.5 0 01-1.897 1.13L3 19l.85-3.933a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                </svg>
                            </button>
                            <a href="{{ route('projects.show', $project) }}" class="text-indigo-400 hover:text-indigo-300 transition-colors inline-flex items-center align-middle" title="View Details">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 2. CARD GRID VIEW (Alternative) -->
    <div id="project-cards-grid" data-view="grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-6" style="display: none;">
        @foreach($projects as $project)
            @php
                $runtimeStatus = $project->runtime_status;
                $statusGlow = match($runtimeStatus) {
                    'healthy' => 'shadow-[0_0_0_1px_rgba(16,185,129,0.08)]',
                    'warning' => 'shadow-[0_0_0_1px_rgba(245,158,11,0.08)]',
                    'critical', 'unreachable' => 'shadow-[0_0_0_1px_rgba(239,68,68,0.08)]',
                    'maintenance' => 'shadow-[0_0_0_1px_rgba(14,165,233,0.08)]',
                    default => '',
                };
                $latestMetric = $project->latestMetricsSnapshot;
                $gitSnapshot = $project->latestGithubSnapshot;
            @endphp
            <div id="project-card-{{ $project->id }}" data-id="{{ $project->id }}" class="group relative bg-zinc-900/30 border border-white/5 {{ $statusGlow }} rounded-lg flex flex-col hover:border-white/10 transition duration-200 overflow-hidden">

                {{-- Top section --}}
                <div class="p-5 flex-1 flex flex-col gap-4">

                    {{-- Header: name + badge --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1 flex gap-2">
                            <!-- Drag Indicator Handle Icon -->
                            <div class="drag-handle flex-shrink-0 flex items-center justify-center w-5 h-10 -ml-1 text-zinc-550 opacity-0 group-hover:opacity-100 transition-opacity duration-200 cursor-grab active:cursor-grabbing hover:text-indigo-400" title="Drag to reorder card">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 mb-1 select-none">
                                    <span class="text-[9px] font-bold text-zinc-600 font-mono tracking-widest uppercase">{{ $project->category ?: 'general' }}</span>
                                    @if($project->ip_address)
                                        <span class="text-zinc-700">·</span>
                                        <span class="inline-flex items-center gap-1 text-[9px] font-mono text-zinc-650">
                                            <span class="h-1 w-1 rounded-full bg-indigo-500/50"></span>{{ $project->ip_address }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-sm font-bold text-zinc-100 leading-tight truncate group-hover:text-white transition-colors">
                                    <a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-400 transition-colors pointer-events-auto">{{ $project->name }}</a>
                                </h3>
                                <span class="mt-0.5 block text-[10px] text-zinc-655 font-mono select-none pointer-events-none">{{ $project->slug }}</span>
                            </div>
                        </div>
                        <div class="project-status-badge-{{ $project->id }} flex-shrink-0 flex items-center gap-1.5">
                            <x-status-badge :status="$runtimeStatus" :reason="$project->runtime_status_reason" />
                        </div>
                    </div>

                    {{-- Metrics Grid --}}
                    <div class="grid grid-cols-3 divide-x divide-white/5 rounded border border-white/5 bg-zinc-950/60 font-mono text-center">
                        <div class="px-2 py-2.5">
                            <span class="block text-[8px] font-bold text-zinc-600 uppercase tracking-widest">Requests</span>
                            <span id="project-card-traffic-{{ $project->id }}" class="mt-1.5 block text-sm font-extrabold text-zinc-200 leading-none">
                                {{ $latestMetric ? number_format($latestMetric->requests_count) : '—' }}
                            </span>
                        </div>
                        <div class="px-2 py-2.5">
                            <span class="block text-[8px] font-bold text-zinc-600 uppercase tracking-widest">Errors</span>
                            <span id="project-card-error-rate-{{ $project->id }}" class="mt-1.5 block text-sm font-extrabold leading-none {{ $latestMetric && $latestMetric->error_rate > 0 ? 'text-status-critical-text' : 'text-zinc-200' }}">
                                {{ $latestMetric ? number_format($latestMetric->error_rate, 1) . '%' : '—' }}
                            </span>
                        </div>
                        <div class="px-2 py-2.5">
                            <span class="block text-[8px] font-bold text-zinc-600 uppercase tracking-widest">Latency</span>
                            <span id="project-card-latency-{{ $project->id }}" class="mt-1.5 block text-sm font-extrabold text-zinc-200 leading-none">
                                {{ $latestMetric && $latestMetric->avg_response_time_ms ? $latestMetric->avg_response_time_ms . 'ms' : '—' }}
                            </span>
                        </div>
                    </div>

                    {{-- Dev stage + git info row --}}
                    <div class="flex items-center justify-between gap-2 text-[9px] font-mono">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-zinc-950 border border-white/5 text-zinc-400 uppercase tracking-wide font-bold">
                            {{ str_replace('_', ' ', $project->status) }}
                        </span>
                        @if($gitSnapshot)
                            <span class="flex items-center gap-1 text-zinc-600 truncate min-w-0">
                                <svg class="h-2.5 w-2.5 flex-shrink-0 text-zinc-700" fill="currentColor" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
                                </svg>
                                <span class="truncate text-zinc-500">{{ $gitSnapshot->last_commit_sha ? substr($gitSnapshot->last_commit_sha, 0, 7) : '—' }}</span>
                                @if($gitSnapshot->open_prs_count > 0)
                                    <span class="ml-1 text-indigo-500/80">{{ $gitSnapshot->open_prs_count }} PR{{ $gitSnapshot->open_prs_count !== 1 ? 's' : '' }}</span>
                                @endif
                            </span>
                        @endif
                    </div>

                    {{-- Tech stack --}}
                    @if($gitSnapshot && is_array($gitSnapshot->detected_technologies) && count($gitSnapshot->detected_technologies) > 0)
                        <x-tech-badges :techs="$gitSnapshot->detected_technologies" :limit="4" />
                    @endif

                </div>

                {{-- AI Insight --}}
                @if($project->latestAiInsight)
                    @php
                        $aiInsight = $project->latestAiInsight;
                        $aiAccent = match($aiInsight->severity) {
                            'critical' => 'text-rose-400',
                            'warning' => 'text-amber-400',
                            default => 'text-indigo-400',
                        };
                    @endphp
                    <div class="px-5 py-2.5 border-t border-white/5 bg-indigo-500/[0.03]">
                        <div class="flex items-start gap-2">
                            <svg class="h-3 w-3 mt-0.5 flex-shrink-0 {{ $aiAccent }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                            <div class="min-w-0">
                                <span class="block text-[9px] font-bold uppercase tracking-widest {{ $aiAccent }}">AI Insight · {{ str_replace('_', ' ', $aiInsight->type) }}</span>
                                <span class="block text-[10px] text-zinc-400 leading-snug mt-0.5" title="{{ $aiInsight->content }}">{{ Str::limit($aiInsight->content, 140) }}</span>
                                <span class="block text-[9px] font-mono text-zinc-600 mt-0.5">{{ $aiInsight->generated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Uptime History Timeline --}}
                <div class="px-5 py-2.5 border-t border-white/5 bg-zinc-950/20 font-mono">
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Uptime History (Last 30)</span>
                        <span class="text-[10px] font-bold {{ ($project->uptime_percentage ?? 100) >= 98 ? 'text-emerald-400' : (($project->uptime_percentage ?? 100) >= 90 ? 'text-amber-400' : 'text-rose-400') }}">
                            {{ $project->uptime_percentage ?? 100 }}%
                        </span>
                    </div>
                    <div class="flex items-center gap-[2px] w-full justify-between">
                        @php
                            $cardSnaps = $project->recentMetricsSnapshots->reverse();
                            $cardPad = max(0, 30 - $cardSnaps->count());
                        @endphp
                        @for($i = 0; $i < $cardPad; $i++)
                            <span class="flex-1 h-3 rounded-[1px] bg-zinc-800/60" data-tooltip="No telemetry snapshot recorded"></span>
                        @endfor
                        @foreach($cardSnaps as $snap)
                            @php
                                $barBg = match($snap->health_status) {
                                    'healthy' => 'bg-emerald-500 hover:bg-emerald-400',
                                    'warning' => 'bg-amber-500 hover:bg-amber-400',
                                    'critical', 'unreachable' => 'bg-rose-500 hover:bg-rose-400',
                                    'maintenance' => 'bg-sky-500 hover:bg-sky-400',
                                    default => 'bg-zinc-700',
                                };
                                $tooltipStr = ($snap->checked_at ? $snap->checked_at->format('M d, H:i') : 'Ping Scan') . ' • Status: ' . strtoupper($snap->health_status) . ($snap->avg_response_time_ms ? ' (' . $snap->avg_response_time_ms . ' ms)' : '');
                            @endphp
                            <span class="flex-1 h-3 rounded-[1px] {{ $barBg }} transition-colors cursor-pointer" data-tooltip="{{ $tooltipStr }}"></span>
                        @endforeach
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-white/5 bg-zinc-950/30 flex items-center justify-between gap-2">
                    <span id="project-card-last-sync-{{ $project->id }}" class="text-[10px] font-mono text-zinc-600 truncate">
                        {{ $latestMetric && $latestMetric->checked_at ? 'Checked ' . $latestMetric->checked_at->diffForHumans() : 'never checked' }}
                    </span>
                    <div class="flex items-center gap-4 font-mono text-[10px]">
                        <button type="button"
                                onclick="triggerSingleProjectSync({{ $project->id }}, '{{ route('projects.sync', $project) }}', this)"
                                class="text-zinc-400 hover:text-emerald-400 transition-colors inline-flex items-center align-middle cursor-pointer"
                                title="{{ $project->status === 'done' ? 'Sync Project Telemetry' : 'Sync Skipped: Development Stage is ' . str_replace('_', ' ', $project->status) }}">
                            <svg class="h-3.5 w-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                        <button type="button" @click.prevent="$dispatch('open-edit-modal', { url: '{{ route('projects.edit-data', $project) }}' })" class="text-zinc-400 hover:text-zinc-200 transition-colors" title="Edit Project">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 17.017a4.5 4.5 0 01-1.897 1.13L3 19l.85-3.933a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                            </svg>
                        </button>
                        <a href="{{ route('projects.show', $project) }}" class="text-indigo-400 hover:text-indigo-300 transition-colors inline-flex items-center" title="View Details">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

@endif

{{-- Data island: project sync metadata, re-read by initProjectSync() after fragment insertion --}}
@php
    $syncData = $projects->map(function ($p) {
        return [
            'id' => $p->id,
            'status' => $p->runtime_status,
            'syncUrl' => route('projects.sync', $p),
        ];
    })->values();
@endphp
<script type="application/json" id="project-sync-data">{!! json_encode($syncData) !!}</script>
