<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssociationRule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'apriori_run_id', 'antecedent', 'consequent', 'antecedent_label', 'consequent_label',
        'support_count', 'support', 'confidence', 'lift',
    ];

    protected function casts(): array
    {
        return [
            'antecedent' => 'array',
            'consequent' => 'array',
            'support' => 'float',
            'confidence' => 'float',
            'lift' => 'float',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AprioriRun::class, 'apriori_run_id');
    }

    // Penjelasan kekuatan hubungan berdasarkan nilai lift
    public function liftLabel(): array
    {
        return match (true) {
            $this->lift > 1.0001 => ['Positif', 'success'],
            $this->lift < 0.9999 => ['Negatif', 'danger'],
            default => ['Independen', 'secondary'],
        };
    }
}
