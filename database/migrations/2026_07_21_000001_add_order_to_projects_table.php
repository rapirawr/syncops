<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('category')->index();
        });

        // Backfill: existing projects keep their creation order as the initial manual order
        $ids = DB::table('projects')->orderBy('created_at')->orderBy('id')->pluck('id');
        foreach ($ids as $position => $id) {
            DB::table('projects')->where('id', $id)->update(['order' => $position + 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['order']);
            $table->dropColumn('order');
        });
    }
};
