<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'model_variant_id',
        'order_prefix',
        'result_type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function modelVariant(): BelongsTo
    {
        return $this->belongsTo(ModelVariant::class);
    }
}
