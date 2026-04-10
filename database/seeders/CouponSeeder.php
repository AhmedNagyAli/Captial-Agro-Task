<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Active coupons
        $this->createActiveCoupons();
        
        // Expired coupons
        $this->createExpiredCoupons();
        
        // Usage limit coupons
        $this->createLimitedCoupons();
        
    }

    private function createActiveCoupons(): void
    {
        // Fixed discount coupons
        Coupon::create([
            'code' => 'SAVE10',
            'type' => Coupon::TYPE_FIXED,
            'value' => 10.00,
            'min_total' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(3),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'WELCOME20',
            'type' => Coupon::TYPE_FIXED,
            'value' => 20.00,
            'min_total' => 50.00,
            'usage_limit' => 100,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(6),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FLASH50',
            'type' => Coupon::TYPE_FIXED,
            'value' => 50.00,
            'min_total' => 200.00,
            'usage_limit' => 50,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addDays(7),
            'is_active' => true,
        ]);

        // Percentage discount coupons
        Coupon::create([
            'code' => 'SAVE10PCT',
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => 10.00,
            'min_total' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(2),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SUMMER20',
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => 20.00,
            'min_total' => 100.00,
            'usage_limit' => 200,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(1),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'BLACKFRIDAY',
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => 30.00,
            'min_total' => 150.00,
            'usage_limit' => 500,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addDays(30),
            'is_active' => true,
        ]);

        // No minimum, unlimited usage
        Coupon::create([
            'code' => 'FREESHIP',
            'type' => Coupon::TYPE_FIXED,
            'value' => 15.00,
            'min_total' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(12),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'LOYALTY15',
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => 15.00,
            'min_total' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(6),
            'is_active' => true,
        ]);
    }

    private function createExpiredCoupons(): void
    {
        // Expired coupons (for testing)
        Coupon::create([
            'code' => 'EXPIRED50',
            'type' => Coupon::TYPE_FIXED,
            'value' => 50.00,
            'min_total' => 100.00,
            'usage_limit' => 10,
            'used_count' => 5,
            'expires_at' => Carbon::now()->subDays(10),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'OLD20PCT',
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => 20.00,
            'min_total' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'expires_at' => Carbon::now()->subMonths(2),
            'is_active' => true,
        ]);
    }

    private function createLimitedCoupons(): void
    {
        // Almost used up coupons
        Coupon::create([
            'code' => 'LIMITED10',
            'type' => Coupon::TYPE_FIXED,
            'value' => 10.00,
            'min_total' => 30.00,
            'usage_limit' => 10,
            'used_count' => 9,
            'expires_at' => Carbon::now()->addDays(15),
            'is_active' => true,
        ]);

        // Inactive coupon
        Coupon::create([
            'code' => 'DISABLED25',
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => 25.00,
            'min_total' => 75.00,
            'usage_limit' => 100,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(3),
            'is_active' => false,
        ]);

        // High minimum purchase
        Coupon::create([
            'code' => 'BIGSPENDER',
            'type' => Coupon::TYPE_FIXED,
            'value' => 100.00,
            'min_total' => 500.00,
            'usage_limit' => 20,
            'used_count' => 0,
            'expires_at' => Carbon::now()->addMonths(6),
            'is_active' => true,
        ]);
    }


}