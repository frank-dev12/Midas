<div class="min-h-[calc(100vh-4rem)] bg-slate-100">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Midas POS</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Punto de venta</h1>
                <p class="text-sm text-slate-600">
                    Tenant: <span class="font-medium text-slate-800">{{ $tenant?->name ?? '—' }}</span>
                    · Moneda {{ $tenant?->currency ?? 'PEN' }}
                </p>
            </div>
            <div class="rounded-lg border border-dashed border-amber-400 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                Offline PWA/sync: cimiento listo (manifest + SW stub). Sync completo pendiente.
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Catálogo --}}
            <section class="lg:col-span-3">
                <div class="mb-3">
                    <label for="pos-search" class="sr-only">Buscar producto</label>
                    <input
                        id="pos-search"
                        type="search"
                        wire:model.live.debounce.250ms="search"
                        placeholder="Buscar por nombre o SKU…"
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    />
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($this->products as $product)
                        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h2 class="font-semibold text-slate-900">{{ $product->name }}</h2>
                                    <p class="text-xs text-slate-500">{{ $product->sku ?: 'Sin SKU' }}</p>
                                </div>
                                <p class="text-sm font-semibold text-slate-800">
                                    S/ {{ number_format($product->price, 2) }}
                                </p>
                            </div>

                            @if ($product->variants->isEmpty())
                                <button
                                    type="button"
                                    wire:click="addProduct({{ $product->id }})"
                                    class="mt-3 w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                                >
                                    Agregar
                                </button>
                            @else
                                <ul class="mt-3 space-y-2">
                                    @foreach ($product->variants as $variant)
                                        <li class="flex items-center justify-between gap-2 text-sm">
                                            <span class="text-slate-700">{{ $variant->label() }}</span>
                                            <button
                                                type="button"
                                                wire:click="addProduct({{ $product->id }}, {{ $variant->id }})"
                                                class="rounded-md bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-500"
                                            >
                                                + S/ {{ number_format($variant->effectivePrice(), 2) }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    @empty
                        <p class="col-span-full rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-600">
                            No hay productos para este tenant. Carga el seeder o crea productos en Filament.
                        </p>
                    @endforelse
                </div>
            </section>

            {{-- Carrito --}}
            <aside class="lg:col-span-2">
                <div class="sticky top-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">Carrito</h2>
                        @if (count($cart))
                            <button type="button" wire:click="clearCart" class="text-xs font-medium text-rose-600 hover:underline">
                                Vaciar
                            </button>
                        @endif
                    </div>

                    <ul class="max-h-80 space-y-3 overflow-y-auto">
                        @forelse ($cart as $line)
                            <li class="border-b border-slate-100 pb-3" wire:key="{{ $line['key'] }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $line['name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $line['variant_label'] }}</p>
                                    </div>
                                    <button type="button" wire:click="remove('{{ $line['key'] }}')" class="text-xs text-slate-400 hover:text-rose-600">✕</button>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="inline-flex items-center rounded-md border border-slate-200">
                                        <button type="button" wire:click="decrement('{{ $line['key'] }}')" class="px-2 py-1 text-sm">−</button>
                                        <span class="min-w-8 px-2 text-center text-sm">{{ $line['qty'] }}</span>
                                        <button type="button" wire:click="increment('{{ $line['key'] }}')" class="px-2 py-1 text-sm">+</button>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-800">
                                        S/ {{ number_format($line['unit_price'] * $line['qty'], 2) }}
                                    </p>
                                </div>
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-slate-500">Carrito vacío</li>
                        @endforelse
                    </ul>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <div class="flex items-center justify-between text-base font-semibold text-slate-900">
                            <span>Total</span>
                            <span>S/ {{ number_format($this->subtotal, 2) }}</span>
                        </div>
                        <button
                            type="button"
                            wire:click="checkoutStub"
                            @disabled(count($cart) === 0)
                            class="mt-4 w-full rounded-lg bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Cobrar (stub)
                        </button>
                        <p class="mt-2 text-center text-[11px] text-slate-400">
                            No emite SUNAT ni descuenta stock aún.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
