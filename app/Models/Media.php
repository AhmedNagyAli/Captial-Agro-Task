<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'mediable_id',
        'mediable_type',
        'disk',
        'path',
        'type',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'sort_order' => 'integer',
    ];


    // Get the parent mediable model (Product, Option, etc.)
    
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }


    public function getUrlAttribute(): string
    {
        if (filter_var($this->path, FILTER_VALIDATE_URL)) {
            return $this->path;
        }

        return asset('storage/' . $this->path);
    }


    public function isImage(): bool
    {
        return $this->type === 'image';
    }


    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

}