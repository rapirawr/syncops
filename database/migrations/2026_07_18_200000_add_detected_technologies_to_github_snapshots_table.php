<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('github_snapshots', function (Blueprint $table) {
            $table->json('detected_technologies')->nullable()->after('stars_count');
        });
    }

    public function down(): void
    {
        Schema::table('github_snapshots', function (Blueprint $table) {
            $table->dropColumn('detected_technologies');
        });
    }
};
