<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminCommand extends Command
{
    protected $signature = 'campusos:create-admin
                            {--name= : Full name}
                            {--email= : Email address}
                            {--username= : Optional username}';

    protected $description = 'Create an institutional administrator account (password is entered interactively)';

    public function handle(): int
    {
        $role = RoleName::InstitutionalAdmin->value;

        if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
            $this->error('Roles not found. Run first: php artisan db:seed --class=RolePermissionSeeder');

            return self::FAILURE;
        }

        $minLength = (int) config('campusos.password.min_length', 10);

        $name = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $username = $this->option('username') ?: $this->ask('Username (optional, press Enter to skip)');
        $password = $this->secret("Password (minimum {$minLength} characters)");
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'username' => $username ?: null,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'alpha_dash', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', "min:{$minLength}", 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'username' => $username ?: null,
            'password' => $password, // hashed by the model cast
            'is_active' => true,
            'mfa_enabled' => true,
            'must_change_password' => false,
        ]);
        $user->email_verified_at = now();
        $user->password_changed_at = now();
        $user->save();

        $user->assignRole($role);

        AuditLog::create([
            'user_id' => null,
            'action' => 'admin.created',
            'module' => 'auth',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'new_values' => ['email' => $user->email, 'role' => $role],
            'ip_address' => null,
            'user_agent' => 'artisan campusos:create-admin',
        ]);

        $this->info("Administrator created: {$user->email}");

        return self::SUCCESS;
    }
}