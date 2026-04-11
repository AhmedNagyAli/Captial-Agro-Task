<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition()
    {
        return [
            'code' => strtoupper($this->faker->lexify('??????')),
            'type' => $this->faker->randomElement(['percentage', 'fixed']),
            'value' => $this->faker->randomFloat(2, 10, 50),
            'min_total' => $this->faker->randomFloat(2, 0, 100),
            'max_discount' => null,
            'usage_limit' => $this->faker->numberBetween(1, 100),
            'used_count' => 0,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}