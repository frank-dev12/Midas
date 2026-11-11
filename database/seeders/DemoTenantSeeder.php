<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo-retail'],
            [
                'name' => 'Demo Retail SAC',
                'ruc' => '20123456789',
                'razon_social' => 'Demo Retail Sociedad Anónima Cerrada',
                'currency' => 'PEN',
                'locale' => 'es_PE',
                'timezone' => 'America/Lima',
                'is_active' => true,
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@midas.test'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $cajero = User::query()->updateOrCreate(
            ['email' => 'cajero@midas.test'],
            [
                'name' => 'Cajero Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['Administrador']);
        $cajero->syncRoles(['Cajero']);

        $tenant->users()->syncWithoutDetaching([$admin->id, $cajero->id]);

        $warehouse = Warehouse::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'code' => 'MAIN',
            ],
            [
                'name' => 'Almacén principal',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $polo = Product::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'sku' => 'POLO-001',
            ],
            [
                'name' => 'Polo básico',
                'description' => 'Polo de algodón — variantes talla/color',
                'price' => 39.90,
                'cost' => 18.00,
                'min_stock' => 5,
                'is_active' => true,
            ]
        );

        $jean = Product::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'sku' => 'JEAN-001',
            ],
            [
                'name' => 'Jean slim',
                'description' => 'Jean denim slim fit',
                'price' => 119.90,
                'cost' => 55.00,
                'min_stock' => 3,
                'is_active' => true,
            ]
        );

        $gorra = Product::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'sku' => 'GORRA-001',
            ],
            [
                'name' => 'Gorra logo',
                'description' => 'Sin variantes',
                'price' => 29.90,
                'cost' => 10.00,
                'min_stock' => 10,
                'is_active' => true,
            ]
        );

        $poloVariants = [
            ['sku' => 'POLO-001-M-NEG', 'size' => 'M', 'color' => 'Negro'],
            ['sku' => 'POLO-001-L-NEG', 'size' => 'L', 'color' => 'Negro'],
            ['sku' => 'POLO-001-M-BLA', 'size' => 'M', 'color' => 'Blanco'],
        ];

        foreach ($poloVariants as $data) {
            $variant = ProductVariant::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'sku' => $data['sku'],
                ],
                [
                    'product_id' => $polo->id,
                    'size' => $data['size'],
                    'color' => $data['color'],
                    'price_override' => null,
                    'is_active' => true,
                ]
            );

            Stock::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $polo->id,
                    'product_variant_id' => $variant->id,
                ],
                ['quantity' => 25]
            );
        }

        $jeanVariant = ProductVariant::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'sku' => 'JEAN-001-32-AZUL',
            ],
            [
                'product_id' => $jean->id,
                'size' => '32',
                'color' => 'Azul',
                'price_override' => 129.90,
                'is_active' => true,
            ]
        );

        Stock::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $jean->id,
                'product_variant_id' => $jeanVariant->id,
            ],
            ['quantity' => 12]
        );

        Stock::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $gorra->id,
                'product_variant_id' => null,
            ],
            ['quantity' => 40]
        );
    }
}
