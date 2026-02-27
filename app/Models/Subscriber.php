<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscriber extends Model
{
    protected $fillable = [
        'email',
        'model_variant_id',
        'order_prefix',
        'last_estimated_date',
        'email_verified_at',
        'verification_token',
        'unsubscribe_token',
    ];

    protected $casts = [
        'last_estimated_date' => 'date',
        'email_verified_at' => 'datetime',
    ];

    public function modelVariant(): BelongsTo
    {
        return $this->belongsTo(ModelVariant::class);
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
}
