<?php

use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\ConfigurationController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\ProductController;
use Illuminate\Support\Facades\Route;



Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/configurations', [ConfigurationController::class, 'store'])->name('config.store');
Route::post('/configurations/select', [ConfigurationController::class, 'select'])->name('config.select');

Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/coupon', [OrderController::class, 'applyCoupon'])->name('orders.coupon.apply');
Route::delete('/orders/{order}/coupon', [OrderController::class, 'removeCoupon'])->name('orders.coupon.remove');