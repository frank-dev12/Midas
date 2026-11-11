<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel Midas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                <p class="text-sm text-gray-600">
                    Tenant activo:
                    <span class="font-semibold text-gray-900">
                        {{ \App\Support\TenantContext::tenant()?->name ?? '—' }}
                    </span>
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Locale es-PE · Moneda PEN · Sin Cashier (demo)
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ url('/admin') }}" class="rounded-lg border border-amber-200 bg-amber-50 p-5 hover:border-amber-400">
                    <h3 class="font-semibold text-amber-900">Filament (backoffice)</h3>
                    <p class="mt-1 text-sm text-amber-800">Productos, almacén, stock, ventas y caja.</p>
                </a>
                <a href="{{ route('pos') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-slate-400">
                    <h3 class="font-semibold text-slate-900">POS</h3>
                    <p class="mt-1 text-sm text-slate-600">Punto de venta Livewire (carrito stub).</p>
                </a>
                <a href="{{ route('tenant.select') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-slate-400">
                    <h3 class="font-semibold text-slate-900">Cambiar tenant</h3>
                    <p class="mt-1 text-sm text-slate-600">Selector post-login.</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
