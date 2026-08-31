<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_listing_requires_user_view_permission(): void
    {
        Sanctum::actingAs($this->user('viewer'));

        $this->getJson('/api/v1/admin/users')->assertForbidden();
    }

    public function test_users_can_be_filtered_by_role(): void
    {
        $actor = $this->authorizedUser(['user.view']);
        $cashierRole = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        $cashier = $this->user('cashier');
        $cashier->assignRole($cashierRole);
        $this->user('customer')->assignRole(
            Role::create(['name' => 'Customer', 'guard_name' => 'web'])
        );
        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/admin/users?role=Cashier')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $cashier->id);
    }

    public function test_super_admin_role_and_users_are_locked(): void
    {
        $actor = $this->authorizedUser(['role.update', 'user.update', 'user.change-status']);
        $superRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin = $this->user('super');
        $superAdmin->assignRole($superRole);
        Sanctum::actingAs($actor);

        $this->putJson("/api/v1/roles/{$superRole->id}", [
            'name' => 'Super Admin',
            'permissions' => [],
        ])->assertForbidden();

        $this->putJson("/api/v1/admin/users/{$superAdmin->id}", [
            'first_name' => $superAdmin->first_name,
            'last_name' => $superAdmin->last_name,
            'username' => $superAdmin->username,
            'email' => $superAdmin->email,
            'mobile' => $superAdmin->mobile,
            'role' => $actor->getRoleNames()->first(),
            'is_active' => true,
        ])->assertForbidden();

        $this->patchJson("/api/v1/admin/users/{$superAdmin->id}/status", [
            'is_active' => false,
        ])->assertForbidden();
    }

    public function test_assigned_roles_cannot_be_deleted(): void
    {
        $actor = $this->authorizedUser(['role.delete']);
        $role = Role::create(['name' => 'Clerk', 'guard_name' => 'web']);
        $this->user('clerk')->assignRole($role);
        Sanctum::actingAs($actor);

        $this->deleteJson("/api/v1/roles/{$role->id}")->assertStatus(409);
    }

    public function test_user_cannot_deactivate_or_delete_their_own_account(): void
    {
        $actor = $this->authorizedUser(['user.change-status', 'user.delete']);
        Sanctum::actingAs($actor);

        $this->patchJson("/api/v1/admin/users/{$actor->id}/status", [
            'is_active' => false,
        ])->assertForbidden();

        $this->deleteJson("/api/v1/admin/users/{$actor->id}")->assertForbidden();
    }

    public function test_trashed_user_can_be_restored_and_permanently_deleted(): void
    {
        $actor = $this->authorizedUser([
            'user.delete',
            'user.restore',
            'user.force-delete',
        ]);
        $target = $this->user('archive');
        Sanctum::actingAs($actor);

        $this->deleteJson("/api/v1/admin/users/{$target->id}")->assertOk();
        $this->putJson("/api/v1/admin/users/{$target->id}/restore")->assertOk();
        $this->assertNotSoftDeleted('users', ['id' => $target->id]);

        $this->deleteJson("/api/v1/admin/users/{$target->id}")->assertOk();
        $this->deleteJson("/api/v1/admin/users/{$target->id}/force-delete")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    private function authorizedUser(array $permissions): User
    {
        $role = Role::create([
            'name' => 'Manager-' . uniqid(),
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role->syncPermissions($permissions);
        $user = $this->user('manager-' . uniqid());
        $user->assignRole($role);

        return $user;
    }

    private function user(string $key): User
    {
        return User::create([
            'first_name' => ucfirst($key),
            'last_name' => 'User',
            'username' => $key . '-' . uniqid(),
            'email' => $key . '-' . uniqid() . '@example.com',
            'mobile' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }
}
