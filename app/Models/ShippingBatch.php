<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingBatch extends Model
{
    protected $fillable = [
        'model_variant_id',
        'ship_date',
        'order_range_start',
        'order_range_end',
        'scraped_at',
    ];

    protected $casts = [
        'ship_date' => 'date',
        'scraped_at' => 'datetime',
    ];

    public function modelVariant(): BelongsTo
    {
        return $this->belongsTo(ModelVariant::class);
    }
}
