<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synthetic_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('total_requests')->default(0);
            $table->integer('successful_requests')->default(0);
            $table->integer('failed_requests')->default(0);
            $table->integer('min_latency_ms')->default(0);
            $table->integer('max_latency_ms')->default(0);
            $table->integer('avg_latency_ms')->default(0);
            $table->integer('p50_latency_ms')->default(0);
            $table->integer('p90_latency_ms')->default(0);
            $table->integer('p99_latency_ms')->default(0);
            $table->json('status_codes')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synthetic_benchmarks');
    }
};
