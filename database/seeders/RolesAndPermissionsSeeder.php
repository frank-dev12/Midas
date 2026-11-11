<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'pos.access',
            'admin.access',
            'products.manage',
            'inventory.manage',
            'sales.view',
            'cash.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('Administrador', 'web');
        $admin->syncPermissions($permissions);

        $cajero = Role::findOrCreate('Cajero', 'web');
        $cajero->syncPermissions([
            'pos.access',
            'sales.view',
            'cash.manage',
        ]);
    }
}
