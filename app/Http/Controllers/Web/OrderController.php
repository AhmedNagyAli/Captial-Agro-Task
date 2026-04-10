<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'config_id' => 'required|exists:configurations,id'
        ]);

        $config = Configuration::with('items')->findOrFail($request->config_id);

        abort_if($config->items->isEmpty(), 400);

        $order = Order::create([
            'subtotal' => $config->total_price,
            'total' => $config->total_price,
            'discount' => 0, // Initialize discount as 0
            'status' => Order::STATUS_PENDING,
        ]);

        foreach ($config->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => 'Custom PC',
                'option_group_name' => $item->option_group_name,
                'option_name' => $item->option_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ]);
        }

        $config->update(['order_id' => $order->id]);

        // Store the order ID in session for coupon application
        session(['pending_order_id' => $order->id]);

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order)
    {
        $order->load('items', 'coupon');
        
        // Get available coupons for suggestions (optional)
        $availableCoupons = Coupon::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where(function($query) {
                $query->whereNull('usage_limit')
                      ->orWhereRaw('used_count < usage_limit');
            })
            ->take(5)
            ->get(['code', 'type', 'value', 'min_total']);

        return Inertia::render('Orders/Summary', [
            'order' => $order,
            'availableCoupons' => $availableCoupons
        ]);
    }

    public function applyCoupon(Request $request, Order $order)
    {
        $request->validate([
            'code' => 'required|string|max:50'
        ]);

        // Check if order already has a coupon
        if ($order->coupon_id) {
            return response()->json([
                'error' => 'A coupon has already been applied to this order'
            ], 422);
        }

        // Check if order is still pending
        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'error' => 'Coupons can only be applied to pending orders'
            ], 422);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'error' => 'Invalid coupon code'
            ], 422);
        }

        if (!$coupon->isValid($order->subtotal)) {
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
            if ($coupon->min_total && $order->subtotal < $coupon->min_total) {
                $errors[] = "Minimum order total of {$coupon->min_total} required for this coupon";
            }
            
            return response()->json([
                'error' => implode(' ', $errors)
            ], 422);
        }

        $discount = $coupon->calculateDiscount($order->subtotal);
        
        // Create coupon usage record
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
        ]);

        $order->update([
            'discount' => $discount,
            'total' => $order->subtotal - $discount,
            'coupon_id' => $coupon->id,
        ]);

        $coupon->incrementUsage();

        $order->load('coupon');

        return response()->json([
            'success' => true,
            'discount' => $discount,
            'total' => $order->total,
            'coupon' => $order->coupon,
            'message' => 'Coupon applied successfully!'
        ]);
    }

    public function removeCoupon(Order $order)
    {
        if (!$order->coupon_id) {
            return response()->json([
                'error' => 'No coupon applied to this order'
            ], 422);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'error' => 'Cannot remove coupon from non-pending order'
            ], 422);
        }

        // Remove coupon usage record
        CouponUsage::where('order_id', $order->id)->delete();

        $order->update([
            'discount' => 0,
            'total' => $order->subtotal,
            'coupon_id' => null,
        ]);

        return response()->json([
            'success' => true,
            'total' => $order->total,
            'message' => 'Coupon removed successfully'
        ]);
    }
}