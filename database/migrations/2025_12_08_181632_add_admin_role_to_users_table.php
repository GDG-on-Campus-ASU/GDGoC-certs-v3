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
        // Drop the existing enum and recreate with the new value
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['leader', 'admin', 'superadmin'])->default('leader')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum values
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['leader', 'superadmin'])->default('leader')->change();
        });
    }
};
