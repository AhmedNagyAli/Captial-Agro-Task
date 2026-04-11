<?php

namespace Database\Factories;

use App\Models\Configuration;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfigurationFactory extends Factory
{
    protected $model = Configuration::class;

    public function definition()
    {
        return [
            'session_id' => $this->faker->uuid(),
            'token' => $this->faker->uuid(),           // ← ADD THIS
            'total_price' => 0,
            'order_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}