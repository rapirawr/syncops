<!DOCTYPE html>
<html lang="id" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 | Pemeliharaan Sistem - SyncOps Telemetry Hub</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['"Instrument Sans"', '"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        zinc: {
                            950: '#09090b',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #09090b;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Instrument Sans', sans-serif;
            letter-spacing: -0.02em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Subtle Grid Pattern */
        .bg-grid-pattern {
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* Scanline Glow */
        @keyframes scanline {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(1000%); }
        }

        .scanline-effect {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(245, 158, 11, 0.5), transparent);
            animation: scanline 6s linear infinite;
            pointer-events: none;
        }

        /* Progress Bar Shimmer */
        @keyframes progress-shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .progress-shimmer {
            background: linear-gradient(
                90deg, 
                #f59e0b 0%, 
                #fbbf24 50%, 
                #f59e0b 100%
            );
            background-size: 200% 100%;
            animation: progress-shimmer 2.5s infinite linear;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(9, 9, 11, 0.8);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }
    </style>
</head>
<body class="h-full min-h-screen bg-zinc-950 text-zinc-100 flex flex-col justify-between relative selection:bg-indigo-500/30 selection:text-indigo-200 antialiased overflow-x-hidden">

    <!-- Top Ambient Glow Overlay (SyncOps Dashboard Aesthetic) -->
    <div class="fixed top-0 left-0 right-0 h-[260px] bg-gradient-to-b from-zinc-900/60 via-amber-500/5 to-transparent pointer-events-none z-0" aria-hidden="true"></div>
    <div class="fixed inset-0 bg-grid-pattern pointer-events-none z-0"></div>

    <!-- Main Container -->
    <div class="relative z-10 flex-1 flex flex-col items-center justify-between p-4 sm:p-6 md:p-10 max-w-6xl mx-auto w-full">

        <!-- Top Header Navigation / Brand Bar -->
        <header class="w-full flex items-center justify-between py-3 px-4 sm:px-6 rounded-2xl bg-zinc-950/60 border border-white/10 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-zinc-800 border border-white/10 flex items-center justify-center shadow-inner">
                    <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold tracking-widest text-zinc-100 uppercase">SyncOps</span>
                        <span class="text-xs text-zinc-600">/</span>
                        <span class="text-xs font-mono font-semibold text-amber-400">TELEMETRY HUB</span>
                    </div>
                    <p class="text-[10px] text-zinc-400 hidden sm:block">Infrastructure & Performance Operations</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-mono font-semibold">
                    <span>MAINTENANCE MODE</span>
                </div>
                <div class="hidden md:flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs font-mono text-zinc-400">
                    <span class="text-zinc-500">NODE:</span>
                    <span class="text-zinc-200 font-bold">SG-CLUSTER-01</span>
                </div>
            </div>
        </header>

        <!-- Main Banner & Content Card -->
        <main class="w-full my-6 space-y-6">

            <!-- Hero Section Panel -->
            <div class="bg-zinc-900/30 border border-white/10 rounded-2xl p-6 sm:p-8 backdrop-blur-xl relative overflow-hidden shadow-2xl">
                <div class="scanline-effect"></div>
                <div class="absolute -right-10 -top-10 w-60 h-60 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-3 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-zinc-800/80 border border-white/10 text-[11px] font-mono text-zinc-300">
                            <span>HTTP 503 · SYSTEM MAINTENANCE</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            Pemeliharaan Sistem Dalam Proses
                        </h1>
                        <p class="text-zinc-400 text-sm leading-relaxed">
                            Kami sedang melakukan peningkatan infrastruktur rutin, pembaruan skema basis data, serta pengoptimalan kluster untuk meningkatkan kinerja dan stabilitas layanan <span class="text-zinc-200 font-semibold">SyncOps Telemetry Hub</span>.
                        </p>
                    </div>

                    <!-- Animated Server Gauge Icon -->
                    <div class="flex-shrink-0 self-center md:self-auto">
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-zinc-950 border border-white/10 p-3 flex flex-col items-center justify-center relative shadow-inner group">
                            <div class="absolute inset-0 bg-gradient-to-b from-amber-500/10 to-transparent rounded-2xl pointer-events-none"></div>
                            <svg class="w-10 h-10 text-amber-400 mb-2 transition-transform duration-500 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.83-5.83M11.42 15.17l2.496-3.03c.317-.384.74-.664 1.208-.81l2.436-.76a1.5 1.5 0 00.998-1.748l-.348-1.564a1.5 1.5 0 00-1.258-1.15l-2.536-.317a2.65 2.65 0 00-1.73.498l-2.73 2.048m-2.536 6.837L3.75 21M3 15l2.25 2.25M6.75 18.75l2.25 2.25M11.42 15.17l-5.326-5.326a2.65 2.65 0 01-.498-1.73l.317-2.536a1.5 1.5 0 011.15-1.258l1.564-.348a1.5 1.5 0 011.748.998l.76 2.436c.146.468.426.891.81 1.208l3.03 2.496" />
                            </svg>
                            <span class="text-[10px] font-mono font-bold text-amber-400 uppercase tracking-widest">OPS RUNNING</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Telemetry Grid (Identical to Dashboard Stat Boxes) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Stat Box 1: Countdown Timer -->
                <div class="bg-zinc-900/30 border border-white/5 hover:border-white/10 transition-all rounded-xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Estimasi Selesai</span>
                        <div class="h-6 w-6 rounded bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span id="countdownDisplay" class="text-2xl font-extrabold text-white font-mono tracking-tight">00:42:15</span>
                    </div>
                    <p class="text-[10px] text-zinc-500 font-mono">Pembaruan sistem otomatis</p>
                </div>

                <!-- Stat Box 2: Progress -->
                <div class="bg-zinc-900/30 border border-white/5 hover:border-white/10 transition-all rounded-xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Progres Pemeliharaan</span>
                        <div class="h-6 w-6 rounded bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-mono">
                            <span class="text-emerald-400 font-bold">85%</span>
                            <span class="text-zinc-500">Tahap 4 dari 5</span>
                        </div>
                        <div class="w-full h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                            <div class="h-full w-[85%] progress-shimmer rounded-full"></div>
                        </div>
                    </div>
                    <p class="text-[10px] text-zinc-500 font-mono">Finalisasi migrasi tabel & indeks</p>
                </div>

                <!-- Stat Box 3: Node Status -->
                <div class="bg-zinc-900/30 border border-white/5 hover:border-white/10 transition-all rounded-xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Status Cluster</span>
                        <div class="h-6 w-6 rounded bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 003 3h7.5a3 3 0 003-3m-13.5 0a3 3 0 013-3h7.5a3 3 0 013 3m0 0l.008 0M6 6.75h12M6 6.75a3 3 0 01-3 3m3-3a3 3 0 003-3h6.5a3 3 0 013 3m-12.5 0l.008 0" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-ping"></span>
                        <span class="text-base font-bold text-white font-mono">ISOLATED / DRAIN</span>
                    </div>
                    <p class="text-[10px] text-zinc-500 font-mono">Trafik dialihkan ke standby queue</p>
                </div>

                <!-- Stat Box 4: Release Version -->
                <div class="bg-zinc-900/30 border border-white/5 hover:border-white/10 transition-all rounded-xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Target Rilis</span>
                        <div class="h-6 w-6 rounded bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-xl font-bold text-white font-mono">v2.4.0-release</span>
                    </div>
                    <p class="text-[10px] text-zinc-500 font-mono">Zero-downtime architecture deployment</p>
                </div>

            </div>

            <!-- Live Diagnostic Terminal Panel -->
            <div class="bg-zinc-950 border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="flex items-center justify-between px-4 py-2.5 bg-zinc-900/60 border-b border-white/5 text-xs font-mono">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-red-500/80 inline-block"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500/80 inline-block"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500/80 inline-block"></span>
                        </div>
                        <span class="text-zinc-400 text-[11px] ml-2 font-bold">system_diagnostics.log</span>
                        <span class="text-zinc-600">|</span>
                        <span class="text-emerald-400 text-[10px] flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            LIVE STREAM
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-zinc-500 text-[11px]">
                        <button onclick="clearConsoleLog()" class="hover:text-zinc-300 transition cursor-pointer">[Clear Console]</button>
                    </div>
                </div>

                <div id="terminalLogBox" class="p-4 h-48 overflow-y-auto font-mono text-[11px] leading-relaxed space-y-1.5 text-zinc-300 bg-zinc-950/90">
                    <div class="text-zinc-500">[17:15:02] <span class="text-indigo-400 font-bold">INFO</span> Maintenance mode initiated by administrator. Isolating SG-CLUSTER-01.</div>
                    <div class="text-zinc-500">[17:16:10] <span class="text-emerald-400 font-bold">SUCCESS</span> Database backup snapshot completed: db_backup_20260721_v2.3.tar.gz</div>
                    <div class="text-zinc-500">[17:17:45] <span class="text-indigo-400 font-bold">INFO</span> Executing Artisan schema migrations: 42 migrations processed cleanly.</div>
                    <div class="text-zinc-500">[17:18:30] <span class="text-amber-400 font-bold">WAIT</span> Clearing Redis cache clusters & rebuilding telemetry index trees...</div>
                    <div class="text-zinc-500">[17:19:12] <span class="text-indigo-400 font-bold">INFO</span> Warm-up sequence triggered for background worker pool [queues: default, high-priority].</div>
                    <div class="text-zinc-300">[17:20:01] <span class="text-emerald-400 font-bold">ACTIVE</span> System operational check running. Ready for final traffic cutover...</div>
                </div>
            </div>

            <!-- Action Row: Server Status Ping & Auto Refresh Bar -->
            <div class="bg-zinc-950 border border-white/5 rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button onclick="runServerStatusCheck()" id="pingBtn" class="flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 text-xs font-semibold font-sans transition duration-200 shadow-lg shadow-indigo-600/20 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Periksa Status Server</span>
                    </button>

                    <div id="pingResult" class="text-xs font-mono text-zinc-400 hidden">
                        <span class="text-emerald-400 font-bold">● PING 24ms</span> · System status: Upgrading
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs font-mono text-zinc-400">
                    <span>Auto-Refresh dalam:</span>
                    <span id="autoRefreshCounter" class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-white font-bold">30s</span>
                </div>
            </div>

        </main>

        <!-- Footer Shortcuts -->
        <footer class="w-full pt-4 pb-2 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-zinc-500 font-sans">
            <div class="flex items-center gap-2">
                <span class="font-bold text-zinc-300 font-mono text-[11px]">SyncOps Telemetry Hub</span>
                <span>·</span>
                <span>© 2026 SyncOps Inc. All rights reserved.</span>
            </div>

            <div class="flex items-center gap-4 text-zinc-400 font-mono text-[11px]">
                <a href="#" class="hover:text-white transition">Halaman Status Utama</a>
                <span>·</span>
                <a href="#" class="hover:text-white transition">Dukungan Sistem</a>
                <span>·</span>
                <a href="#" class="hover:text-white transition">Dokumentasi API</a>
            </div>
        </footer>

    </div>

    <!-- Client-Side JavaScript Logic -->
    <script>
        // Countdown Timer Logic
        let remainingSeconds = 42 * 60 + 15; // 42 minutes 15 seconds
        const countdownEl = document.getElementById('countdownDisplay');

        function updateCountdown() {
            if (remainingSeconds <= 0) {
                countdownEl.innerText = "00:00:00";
                return;
            }
            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;

            countdownEl.innerText = 
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0');

            remainingSeconds--;
        }
        setInterval(updateCountdown, 1000);
        updateCountdown();

        // Auto Refresh Timer Logic (30s cycle)
        let refreshSeconds = 30;
        const refreshEl = document.getElementById('autoRefreshCounter');

        setInterval(() => {
            refreshSeconds--;
            if (refreshSeconds <= 0) {
                refreshSeconds = 30;
                addLogEntry("INFO", "Auto-refresh ping dispatched. System upgrade on schedule.");
            }
            refreshEl.innerText = refreshSeconds + 's';
        }, 1000);

        // Terminal Log Helper
        const terminalBox = document.getElementById('terminalLogBox');

        function addLogEntry(type, message) {
            const now = new Date();
            const timeStr = now.toTimeString().split(' ')[0];
            const typeColor = type === 'SUCCESS' ? 'text-emerald-400 font-bold' : 
                              type === 'WAIT' ? 'text-amber-400 font-bold' : 'text-indigo-400 font-bold';

            const logDiv = document.createElement('div');
            logDiv.className = 'text-zinc-300';
            logDiv.innerHTML = `<span class="text-zinc-500">[${timeStr}]</span> <span class="${typeColor}">${type}</span> ${message}`;
            terminalBox.appendChild(logDiv);
            terminalBox.scrollTop = terminalBox.scrollHeight;
        }

        function clearConsoleLog() {
            terminalBox.innerHTML = '<div class="text-zinc-500">[Console cleared by user]</div>';
        }

        // Server Status Check Action
        function runServerStatusCheck() {
            const btn = document.getElementById('pingBtn');
            const result = document.getElementById('pingResult');
            
            btn.disabled = true;
            btn.classList.add('opacity-75');
            btn.innerHTML = `
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>Memeriksa...</span>
            `;

            setTimeout(() => {
                btn.disabled = false;
                btn.classList.remove('opacity-75');
                btn.innerHTML = `
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Periksa Status Server</span>
                `;
                result.classList.remove('hidden');
                addLogEntry("SUCCESS", "Manual health check ping returned HTTP 503 (Maintenance Mode Active - Node SG-CLUSTER-01).");
            }, 800);
        }
    </script>
</body>
</html>
