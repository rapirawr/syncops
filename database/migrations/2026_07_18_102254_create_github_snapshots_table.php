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
        Schema::create('github_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('default_branch')->nullable();
            $table->string('last_commit_sha')->nullable();
            $table->text('last_commit_message')->nullable();
            $table->timestamp('last_commit_at')->nullable();
            $table->integer('open_issues_count')->default(0);
            $table->integer('open_prs_count')->default(0);
            $table->integer('stars_count')->default(0);
            $table->timestamp('synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_snapshots');
    }
};
