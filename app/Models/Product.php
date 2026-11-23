<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = ['sku', 'name', 'unit', 'price', 'min_stock'];

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    // Stok total = jumlah qty seluruh batch
    public function getStockAttribute(): int
    {
        return (int) $this->batches()->sum('qty');
    }
}
