<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AprioriRun extends Model
{
    protected $fillable = [
        'user_id', 'min_support', 'min_confidence', 'date_from', 'date_to',
        'transaction_count', 'min_support_count', 'itemset_count', 'rule_count', 'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'min_support' => 'float',
            'min_confidence' => 'float',
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function itemsets(): HasMany
    {
        return $this->hasMany(AprioriItemset::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AssociationRule::class);
    }

    // Hasil perhitungan terbaru (dipakai halaman kasir & dashboard)
    public static function latestRun(): ?self
    {
        return static::query()->latest('id')->first();
    }

    public function periodLabel(): string
    {
        if (! $this->date_from && ! $this->date_to) {
            return 'Semua data';
        }

        return ($this->date_from?->format('d/m/Y') ?? 'awal').' s/d '.($this->date_to?->format('d/m/Y') ?? 'sekarang');
    }
}
