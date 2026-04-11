<?php

namespace Database\Factories;

use App\Models\OptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class OptionGroupFactory extends Factory
{
    protected $model = OptionGroup::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement([
                OptionGroup::TYPE_SINGLE,
                OptionGroup::TYPE_MULTIPLE,
                OptionGroup::TYPE_TEXT,
                OptionGroup::TYPE_NUMBER,
                OptionGroup::TYPE_COLOR
            ]),
            'min_selections' => 1,
            'max_selections' => 1,
            'is_required' => $this->faker->boolean(80), // 80% chance true
            'sort_order' => $this->faker->numberBetween(0, 100),
            'validation_rules' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}