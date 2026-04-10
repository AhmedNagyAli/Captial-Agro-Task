<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Collection;

class CouponService
{
    
    // Get available coupons for suggestions
    
    public function getAvailableCoupons(int $limit = 5): Collection
    {
        return Coupon::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where(function($query) {
                $query->whereNull('usage_limit')
                      ->orWhereRaw('used_count < usage_limit');
            })
            ->take($limit)
            ->get(['code', 'type', 'value', 'min_total']);
    }

    
    // Validate a coupon code
    
    public function validateCoupon(string $couponCode, float $subtotal): array
    {
        $coupon = Coupon::where('code', $couponCode)->first();

        if (!$coupon) {
            return ['valid' => false, 'error' => 'Invalid coupon code'];
        }

        if (!$coupon->isValid($subtotal)) {
            $errors = $this->getValidationErrors($coupon, $subtotal);
            return ['valid' => false, 'error' => implode(' ', $errors)];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $coupon->calculateDiscount($subtotal)
        ];
    }

    
    // Get coupon validation errors
    
    private function getValidationErrors(Coupon $coupon, float $subtotal): array
    {
        $errors = [];

        if (!$coupon->is_active) {
            $errors[] = 'This coupon is not active';
        }
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            $errors[] = 'This coupon has expired';
        }
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            $errors[] = 'This coupon has reached its usage limit';
        }
        if ($coupon->min_total && $subtotal < $coupon->min_total) {
            $errors[] = "Minimum order total of {$coupon->min_total} required for this coupon";
        }

        return $errors;
    }
}