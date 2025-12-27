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
        Schema::table('users', function (Blueprint $table) {
            // Optimization: Replace single index on role with composite index on (role, created_at)
            // This optimizes the Admin user list query: User::where('role', 'leader')->orderBy('created_at', 'desc')
            // It allows the database to filter by role and retrieve results in the correct order without a filesort.
            $table->dropIndex(['role']);
            $table->index(['role', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'created_at']);
            $table->index('role');
        });
    }
};
