<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sku',
        'size',
        'color',
        'price_override',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_override' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function label(): string
    {
        $parts = array_filter([$this->size, $this->color]);

        return $parts !== [] ? implode(' / ', $parts) : 'Única';
    }

    public function effectivePrice(): float
    {
        return (float) ($this->price_override ?? $this->product?->price ?? 0);
    }
}
