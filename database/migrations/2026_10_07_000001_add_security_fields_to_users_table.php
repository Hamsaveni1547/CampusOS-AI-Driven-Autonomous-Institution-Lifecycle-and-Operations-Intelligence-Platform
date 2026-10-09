<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->index()->after('password');
            $table->boolean('mfa_enabled')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('mfa_enabled');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_ip');
            $table->boolean('must_change_password')->default(false)->after('password_changed_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['is_active']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'username',
                'phone',
                'is_active',
                'mfa_enabled',
                'last_login_at',
                'last_login_ip',
                'password_changed_at',
                'must_change_password',
            ]);
        });
    }
};
