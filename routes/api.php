<?php

use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CatalogController;
use Illuminate\Support\Facades\Route;


// API Routes for PC Builder System


// Public catalog routes (cached)
Route::prefix('catalog')->group(function () {
    Route::get('groups', [CatalogController::class, 'getGroups']);
    Route::get('options', [CatalogController::class, 'getAllOptions']);
    Route::get('groups/{groupId}/options', [CatalogController::class, 'getGroupOptions']);
});

// Configuration/Builder routes
Route::prefix('configurations')->group(function () {
    Route::post('/', [ConfigurationController::class, 'store']);
    Route::get('/{configId}', [ConfigurationController::class, 'show']);
    Route::post('/{configId}/select', [ConfigurationController::class, 'selectOption']);
    Route::delete('/{configId}/select/{groupId}', [ConfigurationController::class, 'removeOption']);
    Route::put('/{configId}/clear', [ConfigurationController::class, 'clear']);
    Route::get('/{configId}/summary', [ConfigurationController::class, 'summary']);
    Route::put('/{configId}/quantity/{groupId}', [ConfigurationController::class, 'updateQuantity']);
});

// Order routes 
Route::prefix('orders')->group(function () {
    Route::post('/', [OrderController::class, 'store']);                           // POST /api/orders
    Route::get('/{order}', [OrderController::class, 'show']);                     // GET /api/orders/123
    Route::get('/{order}/invoice', [OrderController::class, 'invoice']);          // GET /api/orders/123/invoice
    Route::post('/{order}/coupon', [OrderController::class, 'applyCoupon']);      // POST /api/orders/123/coupon
    Route::delete('/{order}/coupon', [OrderController::class, 'removeCoupon']);   // DELETE /api/orders/123/coupon
    Route::post('/{order}/validate-coupon', [OrderController::class, 'validateCoupon']); // POST /api/orders/123/validate-coupon
    Route::post('/{order}/checkout', [OrderController::class, 'checkout']);       // POST /api/orders/123/checkout
});
