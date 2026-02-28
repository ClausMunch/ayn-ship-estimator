<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip_hash',
        'user_agent',
        'referrer',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
