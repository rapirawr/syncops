<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->foreignId('session_id')
                ->nullable()
                ->after('user_id')
                ->constrained('ai_chat_sessions')
                ->nullOnDelete();

            $table->index('session_id');
        });

        // ── Migrate legacy logs ─────────────────────────────────────────────
        // For every user that has orphaned chat logs (no session_id),
        // create one "Riwayat Lama" session and assign all their orphaned
        // logs to it.
        $orphanUserIds = DB::table('ai_chat_logs')
            ->whereNull('session_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($orphanUserIds as $userId) {
            $sessionId = DB::table('ai_chat_sessions')->insertGetId([
                'user_id'          => $userId,
                'title'            => 'Riwayat Lama',
                'title_generated'  => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('ai_chat_logs')
                ->where('user_id', $userId)
                ->whereNull('session_id')
                ->update(['session_id' => $sessionId]);
        }
    }

    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};
