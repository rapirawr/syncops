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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('ssl_status')->nullable()->after('status'); // valid, warning, critical, expired, unsupported
            $table->string('ssl_issuer')->nullable()->after('ssl_status');
            $table->string('ssl_domain')->nullable()->after('ssl_issuer');
            $table->timestamp('ssl_valid_from')->nullable()->after('ssl_domain');
            $table->timestamp('ssl_valid_to')->nullable()->after('ssl_valid_from');
            $table->integer('ssl_days_left')->nullable()->after('ssl_valid_to');
            $table->timestamp('ssl_last_checked_at')->nullable()->after('ssl_days_left');
            $table->text('ssl_error')->nullable()->after('ssl_last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'ssl_status',
                'ssl_issuer',
                'ssl_domain',
                'ssl_valid_from',
                'ssl_valid_to',
                'ssl_days_left',
                'ssl_last_checked_at',
                'ssl_error',
            ]);
        });
    }
};
