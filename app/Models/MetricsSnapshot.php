<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetricsSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'health_status',
        'requests_count',
        'errors_count',
        'error_rate',
        'avg_response_time_ms',
        'http_status',
        'checked_at',
        'error_message',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'error_rate' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->checked_at = $model->checked_at ?? now();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
