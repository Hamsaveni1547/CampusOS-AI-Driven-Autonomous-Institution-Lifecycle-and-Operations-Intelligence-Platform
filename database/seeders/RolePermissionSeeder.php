<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'profile.view', 'profile.update',
            'users.view', 'users.create', 'users.update', 'users.deactivate',
            'roles.view', 'roles.manage',
            'permissions.manage',
            'audit.view',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $base = ['profile.view', 'profile.update'];

        $map = [
            RoleName::InstitutionalAdmin->value => $permissions, // everything
            RoleName::Hod->value => [...$base, 'users.view'],
            RoleName::HrOfficer->value => [...$base, 'users.view'],
            RoleName::AdministrativeStaff->value => [...$base, 'users.view'],
            RoleName::Student->value => $base,
            RoleName::Faculty->value => $base,
            RoleName::FinanceOfficer->value => $base,
            RoleName::Examiner->value => $base,
            RoleName::Applicant->value => $base,
        ];

        foreach ($map as $roleName => $perms) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
