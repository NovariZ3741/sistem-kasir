<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AprioriItemset extends Model
{
    public $timestamps = false;

    protected $fillable = ['apriori_run_id', 'size', 'items', 'label', 'support_count', 'support'];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'support' => 'float',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AprioriRun::class, 'apriori_run_id');
    }
}
