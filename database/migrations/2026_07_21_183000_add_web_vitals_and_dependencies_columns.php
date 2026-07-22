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
        // Add Core Web Vitals columns to visitor_logs
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->integer('lcp_ms')->nullable()->after('page_title');
            $table->integer('inp_ms')->nullable()->after('lcp_ms');
            $table->decimal('cls', 6, 4)->nullable()->after('inp_ms');
            $table->integer('ttfb_ms')->nullable()->after('cls');
            $table->integer('fcp_ms')->nullable()->after('ttfb_ms');
            $table->string('device_type', 20)->nullable()->after('fcp_ms');
        });

        // Add dependencies column to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->json('dependencies')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropColumn(['lcp_ms', 'inp_ms', 'cls', 'ttfb_ms', 'fcp_ms', 'device_type']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('dependencies');
        });
    }
};
