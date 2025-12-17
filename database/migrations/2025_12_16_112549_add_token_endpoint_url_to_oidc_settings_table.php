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
        Schema::table('oidc_settings', function (Blueprint $table) {
            $table->string('token_endpoint_url')->nullable()->after('userinfo_endpoint_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oidc_settings', function (Blueprint $table) {
            $table->dropColumn('token_endpoint_url');
        });
    }
};
