<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'status',
        'records_found',
        'records_new',
        'error_message',
        'runtime_context',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
