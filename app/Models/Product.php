<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'details',
        'base_price',
        'sale_price',
        'image',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function optionGroups(): HasMany
    {
        return $this->hasMany(OptionGroup::class);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(Configuration::class);
    }

    public function getPriceAttribute(): float
    {
        return $this->sale_price ?? $this->base_price;
    }
}