<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\OptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class OptionFactory extends Factory
{
    protected $model = Option::class;

    public function definition()
    {
        return [
            'option_group_id' => OptionGroup::factory(),
            'name' => $this->faker->word(),
            'price_type' => Option::PRICE_TYPE_FIXED, 
            'price_value' => $this->faker->randomFloat(2, 10, 500),
            'sku' => $this->faker->unique()->bothify('SKU-####'),
            'stock' => $this->faker->numberBetween(0, 100),
            'metadata' => null,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
