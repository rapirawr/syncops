<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatLog extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'question',
        'answer',
        'tool_calls_used',
        'status',
        'error_message',
        'model_used',
    ];

    protected $casts = [
        'tool_calls_used' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }
}

