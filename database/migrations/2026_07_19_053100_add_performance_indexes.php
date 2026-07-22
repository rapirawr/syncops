<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('metrics_snapshots', function (Blueprint $table) {
            $table->index(['project_id', 'checked_at'], 'idx_metrics_project_checked');
            $table->index(['project_id', 'health_status', 'checked_at'], 'idx_metrics_project_status_checked');
            $table->index('checked_at', 'idx_metrics_checked_at');
        });

        Schema::table('github_snapshots', function (Blueprint $table) {
            $table->index(['project_id', 'synced_at'], 'idx_github_project_synced');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('status', 'idx_projects_status');
            $table->index('category', 'idx_projects_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metrics_snapshots', function (Blueprint $table) {
            $table->dropIndex('idx_metrics_project_checked');
            $table->dropIndex('idx_metrics_project_status_checked');
            $table->dropIndex('idx_metrics_checked_at');
        });

        Schema::table('github_snapshots', function (Blueprint $table) {
            $table->dropIndex('idx_github_project_synced');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_status');
            $table->dropIndex('idx_projects_category');
        });
    }
};
