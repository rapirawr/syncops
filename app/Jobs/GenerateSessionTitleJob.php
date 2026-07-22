<?php

namespace App\Jobs;

use App\Models\AiChatSession;
use App\Services\AIOpsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generates a concise title for a chat session from the first user prompt.
 * Runs entirely in the background — the chat response is never blocked by this.
 *
 * Uses the light AI model; falls back to truncating the prompt when the
 * AI is unavailable.
 */
class GenerateSessionTitleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public readonly int $sessionId,
        public readonly string $firstPrompt,
    ) {}

    public function handle(AIOpsService $ai): void
    {
        $session = AiChatSession::find($this->sessionId);

        if (!$session || $session->title_generated) {
            return; // Already done or deleted
        }

        $title = null;
        if ($ai->isConfigured()) {
            $title = $ai->generateSessionTitle($this->firstPrompt);
        }

        $session->update([
            'title'           => $title ?? AiChatSession::titleFromPrompt($this->firstPrompt),
            'title_generated' => true,
        ]);
    }
}
