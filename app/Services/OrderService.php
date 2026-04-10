<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class OrderService
{
    
    // Create an order from a configuration
    
    public function createOrderFromConfiguration(Configuration $configuration): Order
    {
        return DB::transaction(function () use ($configuration) {
            // Create order
            $order = Order::create([
                'subtotal' => $configuration->total_price,
                'total' => $configuration->total_price,
                'discount' => 0,
                'status' => Order::STATUS_PENDING,
            ]);

            // Create order items from configuration items
            foreach ($configuration->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => 'Custom PC',
                    'option_group_name' => $item->option_group_name,
                    'option_name' => $item->option_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ]);
            }

            // Link configuration to order
            $configuration->update(['order_id' => $order->id]);

            // Store order ID in session for coupon application
            Session::put('pending_order_id', $order->id);

            return $order->load('items');
        });
    }

    
    // Get order with relationships
    
    public function getOrderWithDetails(Order $order): Order
    {
        return $order->load('items', 'coupon');
    }

    
    // Apply coupon to order
    
    public function applyCoupon(Order $order, string $couponCode): array
    {
        // Validate order can accept coupon
        $this->validateCouponApplicability($order);

        // Find and validate coupon
        $coupon = $this->findAndValidateCoupon($couponCode, $order->subtotal);

        // Calculate discount
        $discount = $coupon->calculateDiscount($order->subtotal);

        // Apply coupon to order
        return DB::transaction(function () use ($order, $coupon, $discount) {
            // Create coupon usage record
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'order_id' => $order->id,
            ]);

            // Update order
            $order->update([
                'discount' => $discount,
                'total' => $order->subtotal - $discount,
                'coupon_id' => $coupon->id,
            ]);

            // Increment coupon usage count
            $coupon->incrementUsage();

            return [
                'success' => true,
                'discount' => $discount,
                'total' => $order->fresh()->total,
                'coupon' => $coupon,
                'message' => 'Coupon applied successfully!'
            ];
        });
    }

    
    // Remove coupon from order
    
    public function removeCoupon(Order $order): array
    {
        // Validate coupon can be removed
        if (!$order->coupon_id) {
            throw new \Exception('No coupon applied to this order');
        }

        if ($order->status !== Order::STATUS_PENDING) {
            throw new \Exception('Cannot remove coupon from non-pending order');
        }

        return DB::transaction(function () use ($order) {
            // Remove coupon usage record
            CouponUsage::where('order_id', $order->id)->delete();

            // Update order
            $order->update([
                'discount' => 0,
                'total' => $order->subtotal,
                'coupon_id' => null,
            ]);

            return [
                'success' => true,
                'total' => $order->fresh()->total,
                'message' => 'Coupon removed successfully'
            ];
        });
    }

    
    // Validate if order can accept a coupon
    
    private function validateCouponApplicability(Order $order): void
    {
        if ($order->coupon_id) {
            throw new \Exception('A coupon has already been applied to this order');
        }

        if ($order->status !== Order::STATUS_PENDING) {
            throw new \Exception('Coupons can only be applied to pending orders');
        }
    }

    
    // Find and validate coupon
    
    private function findAndValidateCoupon(string $couponCode, float $subtotal): Coupon
    {
        $coupon = Coupon::where('code', $couponCode)->first();

        if (!$coupon) {
            throw new \Exception('Invalid coupon code');
        }

        if (!$coupon->isValid($subtotal)) {
            $errors = $this->getCouponValidationErrors($coupon, $subtotal);
            throw new \Exception(implode(' ', $errors));
        }

        return $coupon;
    }

    
    // Get coupon validation errors
    
    private function getCouponValidationErrors(Coupon $coupon, float $subtotal): array
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