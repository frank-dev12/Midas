<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\DemoTenantSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPosScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_tenant_auto_selects_and_pos_is_reachable(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoTenantSeeder::class);

        $user = User::query()->where('email', 'cajero@midas.test')->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'demo-retail')->firstOrFail();

        $this->actingAs($user);

        // Un solo tenant asignado → selección automática.
        $this->get(route('tenant.select'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($tenant->id, TenantContext::id());

        $this->get(route('pos'))
            ->assertOk()
            ->assertSee('Punto de venta')
            ->assertSee('Polo básico');
    }

    public function test_tenant_selector_lists_multiple_tenants(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoTenantSeeder::class);

        $user = User::query()->where('email', 'admin@midas.test')->firstOrFail();
        $extra = Tenant::query()->create([
            'name' => 'Segunda Tienda',
            'slug' => 'segunda-tienda',
            'currency' => 'PEN',
            'locale' => 'es_PE',
            'is_active' => true,
        ]);
        $user->tenants()->attach($extra);

        $this->actingAs($user);

        $this->get(route('tenant.select'))
            ->assertOk()
            ->assertSee('Demo Retail SAC')
            ->assertSee('Segunda Tienda');

        $this->post(route('tenant.select.store'), ['tenant_id' => $extra->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($extra->id, TenantContext::id());
    }
}
