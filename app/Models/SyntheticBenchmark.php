<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyntheticBenchmark extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'min_latency_ms',
        'max_latency_ms',
        'avg_latency_ms',
        'p50_latency_ms',
        'p90_latency_ms',
        'p99_latency_ms',
        'status_codes',
        'executed_at',
    ];

    protected $casts = [
        'status_codes' => 'array',
        'executed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
