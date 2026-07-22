<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'visitor_id',
        'ip_address',
        'user_agent',
        'path',
        'full_url',
        'referrer',
        'screen_resolution',
        'page_title',
        'lcp_ms',
        'inp_ms',
        'cls',
        'ttfb_ms',
        'fcp_ms',
        'device_type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'cls' => 'decimal:4',
        'lcp_ms' => 'integer',
        'inp_ms' => 'integer',
        'ttfb_ms' => 'integer',
        'fcp_ms' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
