<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Configuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'token',
        'total_price',
        'order_id',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConfigurationItem::class);
    }

    public function recalculateTotal(): void
    {
        $total = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $this->update(['total_price' => $total]);
    }
}