<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatusOverride extends Model
{
    protected $fillable = [
        'project_id',
        'forced_status',
        'reason',
        'active_until',
    ];

    protected $casts = [
        'active_until' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isActive()
    {
        return is_null($this->active_until) || $this->active_until->isFuture();
    }
}
