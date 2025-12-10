<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get database driver for database-agnostic migrations
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: Recreate table with updated constraint
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
            // PostgreSQL and MySQL: Update constraint
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS role_valid");
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'leader'");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET NOT NULL");
            DB::statement("ALTER TABLE users ADD CONSTRAINT role_valid CHECK (role IN ('leader', 'admin', 'superadmin'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get database driver for database-agnostic migrations
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: Recreate table with original constraint
            DB::statement('CREATE TABLE users_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR NOT NULL,
                email VARCHAR NOT NULL UNIQUE,
                email_verified_at DATETIME,
                password VARCHAR,
                org_name VARCHAR,
                role VARCHAR DEFAULT \'leader\' NOT NULL CHECK (role IN (\'leader\', \'superadmin\')),
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
            // PostgreSQL and MySQL: Revert constraint
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS role_valid");
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'leader'");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET NOT NULL");
            DB::statement("ALTER TABLE users ADD CONSTRAINT role_valid CHECK (role IN ('leader', 'superadmin'))");
        }
    }
};
