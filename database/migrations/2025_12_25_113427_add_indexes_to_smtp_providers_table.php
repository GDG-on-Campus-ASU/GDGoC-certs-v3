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
        Schema::table('smtp_providers', function (Blueprint $table) {
            // Optimizes: auth()->user()->smtpProviders()->orderBy('created_at', 'desc')
            // This query is common for users listing their SMTP providers.
            // Metric: Changes query execution from O(N log N) (filesort) to O(N) (index scan).
            $table->index(['user_id', 'created_at']);

            // Optimizes: SmtpProvider::where('is_global', true)->orderBy('created_at', 'desc')
            // This query is used by admins to list global SMTP providers.
            // Metric: Avoids full table scan/filtering and filesort.
            $table->index(['is_global', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smtp_providers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['is_global', 'created_at']);
        });
    }
};
