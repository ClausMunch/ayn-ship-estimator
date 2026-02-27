<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelVariant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'display_order',
        'color_config',
    ];

    protected $casts = [
        'color_config' => 'array',
    ];

    public function shippingBatches(): HasMany
    {
        return $this->hasMany(ShippingBatch::class);
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class);
    }
}
