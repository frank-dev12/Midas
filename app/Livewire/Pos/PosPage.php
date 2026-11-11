<?php

namespace App\Livewire\Pos;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\TenantContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('POS — Midas')]
class PosPage extends Component
{
    /** @var array<int, array{key: string, product_id: int, variant_id: ?int, name: string, variant_label: string, unit_price: float, qty: float}> */
    public array $cart = [];

    public string $search = '';

    public function addProduct(int $productId, ?int $variantId = null): void
    {
        $product = Product::query()->where('is_active', true)->findOrFail($productId);
        $variant = null;

        if ($variantId) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->findOrFail($variantId);
        }

        $key = $product->id.'-'.($variant?->id ?? 'base');
        $unitPrice = $variant?->effectivePrice() ?? (float) $product->price;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] += 1;

            return;
        }

        $this->cart[$key] = [
            'key' => $key,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $product->name,
            'variant_label' => $variant?->label() ?? '—',
            'unit_price' => $unitPrice,
            'qty' => 1.0,
        ];
    }

    public function increment(string $key): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        $this->cart[$key]['qty'] += 1;
    }

    public function decrement(string $key): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        $this->cart[$key]['qty'] -= 1;

        if ($this->cart[$key]['qty'] <= 0) {
            unset($this->cart[$key]);
        }
    }

    public function remove(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->cart)->sum(
            fn (array $line): float => (float) $line['unit_price'] * (float) $line['qty']
        );
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->orderBy('name')
            ->limit(24)
            ->get();
    }

    public function checkoutStub(): void
    {
        // Stub: no persiste venta todavía. TODO: Sale + SaleItem + stock + SUNAT.
        $this->dispatch('pos-checkout-stub', total: $this->subtotal);
        session()->flash('status', 'Checkout stub: carrito listo (total S/ '.number_format($this->subtotal, 2).'). Persistencia de venta pendiente.');
        $this->clearCart();
    }

    public function render()
    {
        return view('livewire.pos.pos-page', [
            'tenant' => TenantContext::tenant(),
        ]);
    }
}
