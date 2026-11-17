<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    protected $fillable = ['title', 'slug', 'excerpt', 'category', 'body', 'author', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function getReadingMinutesAttribute(): int
    {
        return max(1, (int) ceil(str_word_count($this->body) / 200));
    }
}
