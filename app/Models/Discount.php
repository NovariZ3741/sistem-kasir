<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    protected $fillable = [
        'name', 'product_id', 'type', 'value',
        'start_date', 'end_date', 'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Diskon yang sedang berlaku hari ini
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today());
    }
    
    //Status diskon 
    public function getStatusAttribute(): string {
        if (! $this->is_active) {
            return 'nonaktif';
        }
        if (today()->lt($this->start_date)) {
            return 'terjadwal';
        }
        if (today()->gt($this->end_date)) {
            return 'berakhir';
        }
     return 'berlaku';
}

// Potongan per 1 unit barang dengan harga $price
public function amountFor(int $price): int {
    $amount = $this->type === 'percent'
        ? (int) floor($price * $this->value / 100)
        : $this->value;

    return min($amount, $price); // potongan tidak boleh melebihi harga
}
}