<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    use HasFactory;

    const PRICE_TYPE_FIXED = 'fixed';
    const PRICE_TYPE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'option_group_id',
        'name',
        'price_type',
        'price_value',
        'sku',
        'stock',
        'metadata',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_value' => 'decimal:2',
        'stock' => 'integer',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class);
    }

    public function configurationItems(): HasMany
    {
        return $this->hasMany(ConfigurationItem::class);
    }
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable')->ordered();
    }

    public function primaryImage()
    {
        return $this->morphOne(Media::class, 'mediable')
            ->where('type', 'image')
            ->orderBy('sort_order');
    }

    public function dependsOnOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            Option::class,
            'option_dependencies',
            'option_id',
            'depends_on_option_id'
        );
    }

    public function excludedOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            Option::class,
            'option_exclusions',
            'option_id',
            'excluded_option_id'
        );
    }
    public function calculatePrice(float $basePrice = 0, int $quantity = 1): float
    {
        $unitPrice = match ($this->price_type) {
            self::PRICE_TYPE_FIXED => $this->price_value,
            self::PRICE_TYPE_PERCENTAGE => $basePrice * ($this->price_value / 100),
            default => 0,
        };

        return $unitPrice * $quantity;
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    public function decrementStock(int $quantity = 1): void
    {
        $this->decrement('stock', $quantity);
    }

    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('stock', $quantity);
    }
}
