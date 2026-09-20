<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standard Laravel table, referenced by config/auth.php's
        // 'passwords' provider. No password-reset UI has been built in
        // any phase yet (LoginController is a simple custom login only)
        // — this exists so the table is there if/when that feature is
        // added, rather than causing a hard error the first time
        // Password::sendResetLink() is ever called.
        // 2024_01_01_000000_create_users_table already creates this table
        // (together with `sessions`), so creating it again here made a
        // fresh `migrate` fail with "Base table or view already exists".
        // Guarded so this migration is a safe no-op in that case, while
        // still creating the table on any database where it's missing.
        if (Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
