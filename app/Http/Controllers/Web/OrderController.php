<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected CouponService $couponService;

    public function __construct(OrderService $orderService, CouponService $couponService)
    {
        $this->orderService = $orderService;
        $this->couponService = $couponService;
    }

    
    // Create an order from a configuration
    
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'config_id' => 'required|exists:configurations,id'
        ]);

        try {
            $config = Configuration::with('items')->findOrFail($request->config_id);
            
            // Validate configuration has items
            if ($config->items->isEmpty()) {
                return back()->with('error', 'Cannot create order with empty configuration');
            }

            $order = $this->orderService->createOrderFromConfiguration($config);

            return redirect()->route('orders.show', $order);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create order: ' . $e->getMessage());
        }
    }

    
    // Display order summary
    
    public function show(Order $order): Response
    {
        $order = $this->orderService->getOrderWithDetails($order);
        $availableCoupons = $this->couponService->getAvailableCoupons();

        return Inertia::render('Orders/Summary', [
            'order' => $order,
            'availableCoupons' => $availableCoupons
        ]);
    }

    
    // Apply coupon to order
    
    public function applyCoupon(Request $request, Order $order): JsonResponse
    {
        try {
            $result = $this->orderService->applyCoupon($order, $request->code);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    // Remove coupon from order
    
    public function removeCoupon(Order $order): JsonResponse
    {
        try {
            $result = $this->orderService->removeCoupon($order);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
     // Validate coupon without applying
    
    public function validateCoupon(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50'
        ]);

        try {
            $result = $this->couponService->validateCoupon($request->code, $order->subtotal);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }
}