@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="flex min-h-screen flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex h-8 w-8 rounded bg-zinc-900 border border-white/10 items-center justify-center mb-3">
            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
        <h2 class="text-[14px] font-bold tracking-tight text-white uppercase font-mono">Authentication Required</h2>
        <p class="mt-1 text-xs text-zinc-500 font-mono">
            Enter your credentials to access the dashboard.
        </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-zinc-900/30 border border-white/5 px-6 py-8 rounded-lg shadow-sm">
            <form class="space-y-5" action="{{ route('login') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Email Address</label>
                    <div class="mt-1.5">
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                               class="block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 font-mono">Password</label>
                    <div class="mt-1.5">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               class="block w-full rounded border border-white/10 bg-zinc-950 px-3 py-1.5 text-xs text-white placeholder-zinc-700 focus:border-indigo-500 focus:outline-none transition font-mono">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-[10px] font-mono text-status-critical-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox"
                           class="h-3.5 w-3.5 rounded border-white/10 bg-zinc-950 text-indigo-500 focus:ring-0 focus:ring-offset-0">
                    <label for="remember" class="ml-2 block text-xs text-zinc-400 font-mono">Remember session</label>
                </div>

                <div>
                    <button type="submit"
                            class="flex w-full justify-center rounded border border-white/10 bg-zinc-900 py-2 text-xs font-semibold text-white hover:bg-zinc-800 transition">
                        Sign In
                    </button>
                </div>
            </form>

            @if(app()->isLocal())
            <div class="mt-6 border-t border-white/5 pt-5">
                <div class="rounded border border-white/5 bg-zinc-950/60 p-3.5 text-[10px] text-zinc-500 font-mono">
                    <span class="font-bold text-zinc-400">DEV — SEED CREDENTIALS:</span>
                    <div class="mt-1.5 space-y-0.5">
                        <div>EMAIL: <span class="text-zinc-300">admin@example.com</span></div>
                        <div>PASS: <span class="text-zinc-300">password</span></div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
