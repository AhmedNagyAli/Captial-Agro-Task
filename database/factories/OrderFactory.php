<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Configuration;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'order_number' => 'ORD-' . $this->faker->unique()->numberBetween(1000, 9999),
            'configuration_id' => Configuration::factory(),
            'subtotal' => $this->faker->randomFloat(2, 50, 500),
            'discount' => 0,
            'total' => $this->faker->randomFloat(2, 50, 500),
            'status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}