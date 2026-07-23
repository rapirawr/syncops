<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSessionTitleJob;
use App\Models\AiChatLog;
use App\Models\AiChatSession;
use App\Models\AiInsight;
use App\Services\AIOpsService;
use App\Services\AiToolExecutor;
use App\Services\SuggestedPromptGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AiAssistantController extends Controller
{
    // ── Pages ─────────────────────────────────────────────────────────────────

    /**
     * AI Assistant chat page.
     * Loads sidebar session list + messages for the active session (if any).
     */
    public function index(Request $request, SuggestedPromptGenerator $promptGenerator)
    {
        $user = auth()->user();

        // Sidebar: all sessions for this user, newest-active first
        $sessions = AiChatSession::where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'title_generated', 'updated_at']);

        // Active session from query-string ?session=<id>
        $activeSession  = null;
        $history        = collect();

        if ($request->filled('session')) {
            $activeSession = AiChatSession::where('id', $request->session)
                ->where('user_id', $user->id)
                ->first();

            if ($activeSession) {
                $history = AiChatLog::where('session_id', $activeSession->id)
                    ->where('status', 'done')
                    ->oldest()
                    ->get();
            }
        }

        $aiConfigured    = app(AIOpsService::class)->isConfigured();
        $suggestedPrompts = $promptGenerator->getPrompts();
        $selectedModelKey = session('ai_model') ?? config('ai_models.default');
        $modelsList       = config('ai_models.models');
        $providersList    = config('ai_models.providers');

        return view('ai.assistant', compact(
            'sessions', 'activeSession', 'history',
            'aiConfigured', 'suggestedPrompts',
            'selectedModelKey', 'modelsList', 'providersList',
        ));
    }

    // ── Chat Endpoints ────────────────────────────────────────────────────────

    /**
     * Streaming AI chat endpoint via Server-Sent Events (SSE).
     */
    public function chatStream(Request $request, AIOpsService $ai, AiToolExecutor $executor)
    {
        $modelKeys = array_keys(config('ai_models.models'));
        $validated = $request->validate([
            'question'   => 'required|string|max:2000',
            'model'      => 'nullable|string|in:' . implode(',', $modelKeys),
            'session_id' => 'nullable|integer',
        ]);

        // Rate limiting
        $key = 'ai-chat:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return response()->json([
                'success' => false,
                'error'   => 'Terlalu banyak pertanyaan. Silakan tunggu 1 menit.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        if (!$ai->isConfigured()) {
            return response()->json([
                'success' => false,
                'error'   => 'AI Assistant belum dikonfigurasi.',
            ], 400);
        }

        $modelKey = $validated['model'] ?? session('ai_model') ?? config('ai_models.default');
        if (isset($validated['model'])) {
            session(['ai_model' => $validated['model']]);
        }

        // ── Lazy session creation ────────────────────────────────────────────
        $session       = null;
        $isFirstMessage = false;

        if (!empty($validated['session_id'])) {
            // Load existing session for current user
            $session = AiChatSession::where('id', $validated['session_id'])
                ->where('user_id', auth()->id())
                ->first();
        }

        if (!$session) {
            // Create a brand-new session automatically
            $session = AiChatSession::create([
                'user_id'         => auth()->id(),
                'title'           => AiChatSession::titleFromPrompt($validated['question']),
                'title_generated' => false,
            ]);
            $isFirstMessage = true;
        }

        // Create initial chat log
        $chatLog = AiChatLog::create([
            'user_id'    => auth()->id(),
            'session_id' => $session->id,
            'question'   => $validated['question'],
            'status'     => 'processing',
            'model_used' => $modelKey,
        ]);

        // Touch session updated_at so sidebar sorts by last activity
        $session->touch();

        return response()->stream(function () use ($ai, $executor, $validated, $chatLog, $modelKey, $session, $isFirstMessage) {
            while (ob_get_level()) {
                ob_end_flush();
            }

            $fullAnswer  = '';
            $allToolCalls = [];

            $emit = function (string $event, array $data) use (&$fullAnswer, &$allToolCalls) {
                if ($event === 'token' && isset($data['content'])) {
                    $fullAnswer .= $data['content'];
                }
                if ($event === 'done') {
                    $fullAnswer  = $data['answer'] ?? $fullAnswer;
                    $allToolCalls = $data['tool_calls_used'] ?? [];
                }

                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

                if (function_exists('ob_flush')) @ob_flush();
                flush();
            };

            // Emit session_id immediately so the frontend can track it
            $emit('session', ['session_id' => $session->id, 'title' => $session->title]);

            try {
                $ai->chatStreaming($validated['question'], $executor, $emit, $modelKey);

                $chatLog->update([
                    'status'         => 'done',
                    'answer'         => $fullAnswer,
                    'tool_calls_used' => $allToolCalls,
                ]);

                // Update session's updated_at again now that the answer is saved
                $session->touch();

                // ── Async title generation ───────────────────────────────────
                // Only queue on first message; doesn't block the stream response.
                if ($isFirstMessage) {
                    GenerateSessionTitleJob::dispatch($session->id, $validated['question']);
                }
            } catch (\Throwable $e) {
                Log::error("AI stream error: {$e->getMessage()}", ['exception' => $e]);

                $chatLog->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                echo "event: error\n";
                echo 'data: ' . json_encode(['message' => 'Terjadi kesalahan pada layanan AI. Silakan coba lagi.']) . "\n\n";
                if (function_exists('ob_flush')) @ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type'    => 'text/event-stream',
            'Cache-Control'   => 'no-cache',
            'Connection'      => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Direct synchronous AI chat endpoint.
     */
    public function chat(Request $request, AIOpsService $ai, AiToolExecutor $executor)
    {
        $modelKeys = array_keys(config('ai_models.models'));
        $validated = $request->validate([
            'question'   => 'required|string|max:2000',
            'model'      => 'nullable|string|in:' . implode(',', $modelKeys),
            'session_id' => 'nullable|integer',
        ]);

        $key = 'ai-chat:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return response()->json([
                'success' => false,
                'error'   => 'Terlalu banyak pertanyaan. Silakan tunggu 1 menit.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        if (!$ai->isConfigured()) {
            return response()->json([
                'success' => false,
                'error'   => 'AI Assistant belum dikonfigurasi (NVIDIA_NIM_API_KEY belum diset).',
            ], 400);
        }

        $modelKey = $validated['model'] ?? session('ai_model') ?? config('ai_models.default');
        if (isset($validated['model'])) {
            session(['ai_model' => $validated['model']]);
        }

        // Lazy session creation
        $session        = null;
        $isFirstMessage = false;

        if (!empty($validated['session_id'])) {
            $session = AiChatSession::where('id', $validated['session_id'])
                ->where('user_id', auth()->id())
                ->first();
        }

        if (!$session) {
            $session = AiChatSession::create([
                'user_id'         => auth()->id(),
                'title'           => AiChatSession::titleFromPrompt($validated['question']),
                'title_generated' => false,
            ]);
            $isFirstMessage = true;
        }

        $chatLog = AiChatLog::create([
            'user_id'    => auth()->id(),
            'session_id' => $session->id,
            'question'   => $validated['question'],
            'status'     => 'processing',
            'model_used' => $modelKey,
        ]);

        $session->touch();

        try {
            $result = $ai->chat($validated['question'], $executor, $modelKey);

            if (!$result || empty(trim($result['answer'] ?? ''))) {
                $chatLog->update([
                    'status'        => 'failed',
                    'error_message' => 'Layanan AI tidak memberikan respons yang valid.',
                ]);
                return response()->json(['success' => false, 'error' => 'Layanan AI tidak memberikan respons yang valid.'], 500);
            }

            $toolsUsed = collect($result['tool_calls_used'] ?? [])->pluck('tool')->unique()->values();

            $chatLog->update([
                'status'          => 'done',
                'answer'          => $result['answer'],
                'tool_calls_used' => $result['tool_calls_used'] ?? [],
            ]);

            $session->touch();

            if ($isFirstMessage) {
                GenerateSessionTitleJob::dispatch($session->id, $validated['question']);
            }

            return response()->json([
                'success'    => true,
                'session_id' => $session->id,
                'id'         => $chatLog->id,
                'question'   => $chatLog->question,
                'answer'     => $result['answer'],
                'tools'      => $toolsUsed,
            ]);
        } catch (\Throwable $e) {
            Log::error("AiAssistantController error: {$e->getMessage()}", ['exception' => $e]);
            $chatLog->update(['status' => 'failed', 'error_message' => 'Terjadi kesalahan sistem.']);
            return response()->json(['success' => false, 'error' => 'Terjadi kesalahan pada layanan AI. Silakan coba lagi.'], 500);
        }
    }

    // ── Session API ───────────────────────────────────────────────────────────

    /**
     * Load messages for a specific session.
     * Used by the frontend when the user clicks a sidebar entry.
     */
    public function loadSession(AiChatSession $session)
    {
        if ($session->user_id !== auth()->id()) {
            abort(403);
        }

        $logs = AiChatLog::where('session_id', $session->id)
            ->where('status', 'done')
            ->oldest()
            ->get(['id', 'question', 'answer', 'tool_calls_used', 'model_used', 'created_at']);

        return response()->json([
            'success'  => true,
            'session'  => ['id' => $session->id, 'title' => $session->title],
            'messages' => $logs,
        ]);
    }

    /**
     * Fetch updated title for a session (polled by frontend after first message).
     */
    public function sessionTitle(AiChatSession $session)
    {
        if ($session->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'id'              => $session->id,
            'title'           => $session->title,
            'title_generated' => $session->title_generated,
        ]);
    }

    /**
     * Delete a session and all its chat logs.
     */
    public function deleteSession(AiChatSession $session)
    {
        if ($session->user_id !== auth()->id()) {
            abort(403);
        }

        $session->delete();

        return response()->json(['success' => true]);
    }

    // ── Misc ──────────────────────────────────────────────────────────────────

    public function models()
    {
        return response()->json([
            'success'  => true,
            'default'  => config('ai_models.default'),
            'models'   => config('ai_models.models'),
            'selected' => session('ai_model') ?? config('ai_models.default'),
        ]);
    }

    /**
     * Clear ALL sessions and chat history for the current user.
     */
    public function clearHistory()
    {
        AiChatSession::where('user_id', auth()->id())->delete();

        return response()->json(['success' => true]);
    }

    public function resolveInsight(AiInsight $insight)
    {
        $insight->update(['resolved_at' => now()]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'AI insight resolved.');
    }
}
