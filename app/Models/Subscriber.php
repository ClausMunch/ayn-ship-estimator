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
        'verification_sent_at',
        'delivery_status',
        'delivery_error',
        'bounced_at',
        'shipped_confirmation_sent_at',
        'shipped_confirmed_at',
        'delivered_confirmation_sent_at',
        'delivered_confirmed_at',
        'verification_token',
        'unsubscribe_token',
    ];

    protected $casts = [
        'last_estimated_date' => 'date',
        'email_verified_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'bounced_at' => 'datetime',
        'shipped_confirmation_sent_at' => 'datetime',
        'shipped_confirmed_at' => 'datetime',
        'delivered_confirmation_sent_at' => 'datetime',
        'delivered_confirmed_at' => 'datetime',
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
