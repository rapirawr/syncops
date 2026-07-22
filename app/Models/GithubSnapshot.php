<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GithubSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'default_branch',
        'last_commit_sha',
        'last_commit_message',
        'last_commit_at',
        'open_issues_count',
        'open_prs_count',
        'stars_count',
        'detected_technologies',
        'synced_at',
    ];

    protected $casts = [
        'last_commit_at'          => 'datetime',
        'synced_at'               => 'datetime',
        'detected_technologies'   => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->synced_at = $model->synced_at ?? now();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
