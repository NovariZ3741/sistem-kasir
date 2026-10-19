<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProductBatch extends Model
{
    protected $fillable = ['product_id', 'qty', 'expired_at', 'received_at'];

    protected $casts = [
        'expired_at' => 'date',
        'received_at' => 'date',
    ];

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
        }
    
    //Fungsi menghitung tanggal kadaluwarsa  
    public function getDaysToExpiryAttribute(): ?int {
        return $this->expired_at
        ? (int) today()->diffInDays($this->expired_at, false)
        : null;
    }

    // Batch yang boleh dijual: masih ada stok dan belum kadaluarsa
    public function scopeSellable(Builder $query): Builder {
        return $query->where('qty', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expired_at')
                ->orWhereDate('expired_at', '>=', today()->toDateString());
            });
    }
}