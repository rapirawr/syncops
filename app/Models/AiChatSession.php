<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatSession extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'title_generated',
    ];

    protected $casts = [
        'title_generated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chatLogs()
    {
        return $this->hasMany(AiChatLog::class, 'session_id');
    }

    /**
     * Generate a short title from the first user message.
     * Truncates to 60 characters and adds ellipsis if needed.
     */
    public static function titleFromPrompt(string $prompt): string
    {
        $trimmed = trim($prompt);
        return mb_strlen($trimmed) > 60
            ? mb_substr($trimmed, 0, 60) . '…'
            : $trimmed;
    }
}
