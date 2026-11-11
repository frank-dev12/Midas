<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-900">Seleccionar empresa</h1>
        <p class="mt-1 text-sm text-gray-600">Elige el tenant con el que trabajarás en esta sesión.</p>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($tenants->isEmpty())
        <p class="text-sm text-gray-600">
            Tu usuario no pertenece a ningún tenant. Pide a un administrador que te asigne uno o ejecuta el seeder demo.
        </p>
    @else
        <form method="POST" action="{{ route('tenant.select.store') }}" class="space-y-4">
            @csrf

            <div class="space-y-2">
                @foreach ($tenants as $tenant)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 hover:border-amber-400 {{ (int) $currentTenantId === (int) $tenant->id ? 'border-amber-500 bg-amber-50' : 'bg-white' }}">
                        <input
                            type="radio"
                            name="tenant_id"
                            value="{{ $tenant->id }}"
                            class="border-gray-300 text-amber-600 focus:ring-amber-500"
                            @checked((int) $currentTenantId === (int) $tenant->id || $tenants->count() === 1)
                            required
                        />
                        <span>
                            <span class="block font-medium text-gray-900">{{ $tenant->name }}</span>
                            <span class="block text-xs text-gray-500">
                                {{ $tenant->razon_social ?: $tenant->slug }}
                                · {{ $tenant->currency }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('tenant_id')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror

            <x-primary-button class="w-full justify-center">
                Continuar
            </x-primary-button>
        </form>
    @endif
</x-guest-layout>
