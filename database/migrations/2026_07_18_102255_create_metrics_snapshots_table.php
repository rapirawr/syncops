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
        Schema::create('metrics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('health_status', ['healthy', 'warning', 'critical', 'unreachable']);
            $table->integer('requests_count')->default(0);
            $table->integer('errors_count')->default(0);
            $table->decimal('error_rate', 5, 2)->nullable();
            $table->integer('avg_response_time_ms')->nullable();
            $table->integer('http_status')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics_snapshots');
    }
};
