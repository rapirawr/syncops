<?php

namespace App\Jobs;

use App\Models\AiChatLog;
use App\Services\AIOpsService;
use App\Services\AiToolExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processes one AI chat question asynchronously so the HTTP endpoint
 * never blocks on the NVIDIA NIM API. The frontend polls the chat log status.
 */
class ProcessAiChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 180; // tool-calling loops can take several API round-trips

    public function __construct(protected AiChatLog $chatLog)
    {
    }

    public function handle(AIOpsService $ai, AiToolExecutor $executor): void
    {
        $this->chatLog->update(['status' => 'processing']);

        if (!$ai->isConfigured()) {
            $this->chatLog->update([
                'status' => 'failed',
                'error_message' => 'AI assistant is not configured (missing NVIDIA_NIM_API_KEY).',
            ]);
            return;
        }

        try {
            $result = $ai->chat($this->chatLog->question, $executor);

            if (!$result) {
                $this->chatLog->update([
                    'status' => 'failed',
                    'error_message' => 'AI service unavailable, please try again later.',
                ]);
                return;
            }

            $this->chatLog->update([
                'status' => 'done',
                'answer' => $result['answer'],
                'tool_calls_used' => $result['tool_calls_used'],
            ]);
        } catch (\Throwable $e) {
            Log::warning("ProcessAiChatJob failed for chat #{$this->chatLog->id}: {$e->getMessage()}");
            $this->chatLog->update([
                'status' => 'failed',
                'error_message' => 'Unexpected error while processing your question.',
            ]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->chatLog->update([
            'status' => 'failed',
            'error_message' => 'Processing timed out or crashed.',
        ]);
    }
}
