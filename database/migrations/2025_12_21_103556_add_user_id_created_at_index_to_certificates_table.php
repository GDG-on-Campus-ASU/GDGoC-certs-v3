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
        Schema::table('certificates', function (Blueprint $table) {
            // Optimization: Add composite index for user_id and created_at
            // This optimizes the common query pattern: auth()->user()->certificates()->latest()
            // Expected impact: Eliminates filesort for certificate listing pages, reducing query time for users with many certificates.
            // Metric: Changes query execution from O(N log N) (filesort) to O(N) (index scan) or O(limit) for paginated queries.
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
