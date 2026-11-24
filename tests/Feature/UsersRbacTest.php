<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_users_crud_and_status_toggles(): void
    {
        $admin = User::factory()->create(['name' => 'Dueño Bodega']);
        $this->actingAs($admin);

        Role::syncSystemCatalog();
        $cajeroRole = Role::query()->where('slug', 'cajero')->firstOrFail();

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Dueño Bodega')
            ->assertSee('Super Administrador');

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Registrar integrante del equipo');

        $this->post(route('admin.users.store'), [
            'name' => 'Valeria Caja',
            'email' => 'valeria@bodega.test',
            'phone' => '987 654 321',
            'job_title' => 'Cajera turno tarde',
            'role_id' => $cajeroRole->id,
            'active' => '1',
            'password' => 'ClaveSegura2026!',
            'password_confirmation' => 'ClaveSegura2026!',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'valeria@bodega.test')->firstOrFail();
        $this->assertSame('51987654321', $created->phone);
        $this->assertTrue($created->isActive());
        $this->assertSame($cajeroRole->id, $created->role_id);

        $this->get(route('admin.users.edit', $created))
            ->assertOk()
            ->assertSee('Valeria Caja');

        $this->put(route('admin.users.update', $created), [
            'name' => 'Valeria Caja Principal',
            'email' => 'valeria@bodega.test',
            'phone' => '987654321',
            'job_title' => 'Jefa de caja',
            'role_id' => $cajeroRole->id,
            'active' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $created->refresh();
        $this->assertSame('Valeria Caja Principal', $created->name);
        $this->assertSame('Jefa de caja', $created->job_title);

        $this->patch(route('admin.users.toggle', $created))
            ->assertRedirect();
        $this->assertFalse($created->fresh()->isActive());

        $this->patch(route('admin.users.toggle', $created))
            ->assertRedirect();
        $this->assertTrue($created->fresh()->isActive());

        $this->delete(route('admin.users.destroy', $created))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $created->id]);
    }

    public function test_super_admin_can_create_duplicate_edit_and_delete_custom_rbac_roles(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Role::syncSystemCatalog();
        $superRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $cajeroRole = Role::query()->where('slug', 'cajero')->firstOrFail();

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Matriz completa de permisos por rol')
            ->assertSee('Cajero / Ventas');

        $permIds = Permission::query()
            ->whereIn('key', ['dashboard.view', 'sales.view', 'sales.create'])
            ->pluck('id')
            ->all();

        $this->post(route('admin.roles.store'), [
            'name' => 'Turno Noche',
            'slug' => '',
            'description' => 'Solo caja y consulta de panel nocturno.',
            'color' => 'amber',
            'permissions' => $permIds,
        ])->assertRedirect(route('admin.roles.index'));

        $customRole = Role::query()->where('slug', 'turno-noche')->firstOrFail();
        $this->assertCount(3, $customRole->permissions);

        $this->get(route('admin.roles.create', ['duplicate' => $cajeroRole->id]))
            ->assertOk()
            ->assertSee('Cajero / Ventas (Copia)');

        $updatedPerms = Permission::query()
            ->whereIn('key', ['sales.view', 'sales.create'])
            ->pluck('id')
            ->all();

        $this->put(route('admin.roles.update', $customRole), [
            'name' => 'Caja Nocturna',
            'slug' => 'caja-nocturna',
            'description' => 'Actualizado para cierre nocturno.',
            'color' => 'sky',
            'permissions' => $updatedPerms,
        ])->assertRedirect(route('admin.roles.index'));

        $customRole->refresh();
        $this->assertSame('caja-nocturna', $customRole->slug);
        $this->assertCount(2, $customRole->permissions);

        // Cannot delete system role
        $this->from(route('admin.roles.index'))
            ->delete(route('admin.roles.destroy', $superRole))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['id' => $superRole->id]);

        // Cannot delete role while assigned to a user
        $worker = User::factory()->withRole($customRole)->create();
        $this->from(route('admin.roles.index'))
            ->delete(route('admin.roles.destroy', $customRole))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHasErrors('role');

        // Reassign user, then delete works cleanly
        $worker->update(['role_id' => $cajeroRole->id]);
        $this->delete(route('admin.roles.destroy', $customRole))
            ->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_rbac_enforces_route_permissions_and_filters_navigation_for_cajero(): void
    {
        $cajero = User::factory()->withRole('cajero')->create();
        $this->actingAs($cajero);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Usuarios y equipo')
            ->assertDontSee('Roles y permisos')
            ->assertDontSee('Editar página web');

        $this->get(route('admin.sales.index'))->assertOk();
        $this->get(route('admin.clients.index'))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.contacts.index'))->assertOk();

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.site.edit'))->assertForbidden();
        $this->get(route('admin.products.create'))->assertForbidden();
        $this->get(route('admin.suppliers.create'))->assertForbidden();
        $this->get(route('admin.export', ['type' => 'products']))->assertForbidden();

        $this->postJson(route('admin.chat'), ['message' => 'hola'])
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_anti_lockout_guards_protect_self_and_last_active_super_admin(): void
    {
        Role::syncSystemCatalog();
        $cajeroRole = Role::query()->where('slug', 'cajero')->firstOrFail();

        $soleAdmin = User::factory()->create();
        $this->actingAs($soleAdmin);

        // Cannot suspend self
        $this->from(route('admin.users.index'))
            ->patch(route('admin.users.toggle', $soleAdmin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');
        $this->assertTrue($soleAdmin->fresh()->isActive());

        // Cannot delete self
        $this->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $soleAdmin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $soleAdmin->id]);

        // Cannot demote or deactivate self via edit form
        $this->from(route('admin.users.edit', $soleAdmin))
            ->put(route('admin.users.update', $soleAdmin), [
                'name' => $soleAdmin->name,
                'email' => $soleAdmin->email,
                'role_id' => $cajeroRole->id,
                'active' => '1',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.edit', $soleAdmin))
            ->assertSessionHasErrors('role_id');
        $this->assertTrue($soleAdmin->fresh()->isSuperAdmin());

        // With a second Super Admin, the second can be suspended, leaving the sole active one protected
        $secondAdmin = User::factory()->create();
        $this->patch(route('admin.users.toggle', $secondAdmin))->assertRedirect();
        $this->assertFalse($secondAdmin->fresh()->isActive());
    }

    public function test_inactive_users_cannot_authenticate_and_active_sessions_are_evicted(): void
    {
        $suspended = User::factory()->inactive()->withRole('cajero')->create([
            'password' => 'ClaveSegura2026!',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $suspended->email,
                'password' => 'ClaveSegura2026!',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $activeUser = User::factory()->withRole('cajero')->create([
            'password' => 'ClaveSegura2026!',
        ]);

        $this->post(route('login.store'), [
            'email' => $activeUser->email,
            'password' => 'ClaveSegura2026!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($activeUser);
        $this->assertNotNull($activeUser->fresh()->last_login_at);

        // Suspend while logged in -> next protected route logs user out cleanly
        $activeUser->update(['active' => false]);

        $this->get(route('admin.sales.index'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_redirects_user_without_dashboard_permission_to_first_allowed_module(): void
    {
        Role::syncSystemCatalog();
        $salesOnlyPermIds = Permission::query()
            ->whereIn('key', ['sales.view', 'sales.create'])
            ->pluck('id')
            ->all();

        $salesOnlyRole = Role::query()->create([
            'name' => 'Solo Caja Rápida',
            'slug' => 'solo-caja-rapida',
            'color' => 'emerald',
        ]);
        $salesOnlyRole->permissions()->sync($salesOnlyPermIds);

        $seller = User::factory()->withRole($salesOnlyRole)->create([
            'password' => 'ClaveSegura2026!',
        ]);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'ClaveSegura2026!',
        ])->assertRedirect(route('admin.sales.index'));

        $this->get(route('admin.sales.index'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }
}
