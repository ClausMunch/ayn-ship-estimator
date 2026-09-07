<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subscriber_id',
        'recipient',
        'type',
        'subject',
        'message_id',
        'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];
}
