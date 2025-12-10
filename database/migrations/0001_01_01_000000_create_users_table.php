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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('org_name')->nullable();
            $table->string('role')->default('leader');
            $table->string('status')->default('active');
            $table->text('termination_reason')->nullable();
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Add check constraints for role and status (database-agnostic)
        $driver = Schema::connection(null)->getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE ADD CONSTRAINT, but supports CHECK in CREATE TABLE
            // Recreate table with constraints
            DB::statement('CREATE TABLE users_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR NOT NULL,
                email VARCHAR NOT NULL UNIQUE,
                email_verified_at DATETIME,
                password VARCHAR,
                org_name VARCHAR,
                role VARCHAR DEFAULT \'leader\' NOT NULL CHECK (role IN (\'leader\', \'admin\', \'superadmin\')),
                status VARCHAR DEFAULT \'active\' NOT NULL CHECK (status IN (\'active\', \'suspended\', \'terminated\')),
                termination_reason TEXT,
                oauth_provider VARCHAR,
                oauth_id VARCHAR,
                remember_token VARCHAR,
                created_at DATETIME,
                updated_at DATETIME
            )');
            DB::statement('INSERT INTO users_new SELECT * FROM users');
            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');
        } else {
            // PostgreSQL and MySQL support ALTER TABLE ADD CONSTRAINT
            DB::statement("ALTER TABLE users ADD CONSTRAINT role_valid CHECK (role IN ('leader', 'admin', 'superadmin'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT status_valid CHECK (status IN ('active', 'suspended', 'terminated'))");
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
