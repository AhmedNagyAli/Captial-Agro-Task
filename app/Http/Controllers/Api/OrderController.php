<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected CouponService $couponService;

    public function __construct(OrderService $orderService, CouponService $couponService)
    {
        $this->orderService = $orderService;
        $this->couponService = $couponService;
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'config_id' => 'required|exists:configurations,id'
        ]);

        try {
            $config = Configuration::with('items')->findOrFail($request->config_id);

            if ($config->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot create order with empty configuration'
                ], 422);
            }

            $order = $this->orderService->createOrderFromConfiguration($config);

            return response()->json([
                'success' => true,
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create order'
            ], 500);
        }
    }

    public function show(Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderWithDetails($order);
            $availableCoupons = $this->couponService->getAvailableCoupons();

            return response()->json([
                'success' => true,
                'order' => $order,
                'available_coupons' => $availableCoupons
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch order'
            ], 500);
        }
    }

    public function invoice(Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderWithDetails($order);

            return response()->json([
                'success' => true,
                'invoice' => [
                    'order_id' => $order->id,
                    'subtotal' => $order->subtotal,
                    'discount' => $order->discount,
                    'total' => $order->total,
                    'status' => $order->status,
                    'items' => $order->items
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate invoice'
            ], 500);
        }
    }

    public function applyCoupon(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50'
        ]);

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

    public function checkout(Order $order): JsonResponse
    {
        try {
            $order->update([
                'status' => 'processing',
                'payment_status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checkout initiated',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Checkout failed'
            ], 500);
        }
    }
}
