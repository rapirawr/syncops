<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'severity',
        'title',
        'content',
        'priority_rank',
        'priority_reason',
        'generated_at',
        'resolved_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'resolved_at'  => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope: unresolved insights only.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope: insights that qualify as alerts (warning/critical).
     */
    public function scopeAlerts($query)
    {
        return $query->whereIn('severity', ['warning', 'critical']);
    }
}
