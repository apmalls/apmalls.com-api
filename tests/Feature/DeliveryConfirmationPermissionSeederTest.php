<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeliveryConfirmationPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_confirmation_permissions_are_seeded_and_assigned_to_operational_managers(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);

        $permissionNames = [
            'delivery-confirmation.list',
            'delivery-confirmation.view',
            'delivery-confirmation.resolve',
        ];

        foreach ($permissionNames as $permissionName) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach (['Super Admin', 'Admin', 'Store Manager'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $this->assertTrue($role->hasAllPermissions($permissionNames));
        }

        foreach (['Cashier', 'Delivery Boy', 'Customer'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $this->assertFalse($role->hasAnyPermission($permissionNames));
        }
    }

    public function test_permission_and_role_seeders_are_idempotent(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        $this->seed([PermissionSeeder::class, RolePermissionSeeder::class]);

        $this->assertSame(1, Permission::query()
            ->where('name', 'delivery-confirmation.resolve')
            ->where('guard_name', 'web')
            ->count());
        $this->assertTrue(Role::findByName('Admin', 'web')
            ->hasPermissionTo('delivery-confirmation.resolve'));
        $this->assertTrue(Role::findByName('Store Manager', 'web')
            ->hasPermissionTo('delivery-confirmation.resolve'));
    }
}
