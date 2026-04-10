<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\OptionGroup;
use App\Models\Option;

class PcPartsSeeder extends Seeder
{
    public function run(): void
    {
        // ================= CPU =================
        $cpu = OptionGroup::create([
            'name' => 'CPU',
            'type' => 'single',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        Option::insert([
            ['option_group_id' => $cpu->id, 'name' => 'Ryzen 5 5600X', 'price_type' => 'fixed', 'price_value' => 180],
            ['option_group_id' => $cpu->id, 'name' => 'Ryzen 7 5800X', 'price_type' => 'fixed', 'price_value' => 260],
            ['option_group_id' => $cpu->id, 'name' => 'Intel i5 12400F', 'price_type' => 'fixed', 'price_value' => 170],
            ['option_group_id' => $cpu->id, 'name' => 'Intel i7 12700K', 'price_type' => 'fixed', 'price_value' => 320],
        ]);

        // ================= GPU =================
        $gpu = OptionGroup::create([
            'name' => 'GPU',
            'type' => 'single',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        Option::insert([
            ['option_group_id' => $gpu->id, 'name' => 'RTX 3060', 'price_type' => 'fixed', 'price_value' => 300],
            ['option_group_id' => $gpu->id, 'name' => 'RTX 4060', 'price_type' => 'fixed', 'price_value' => 400],
            ['option_group_id' => $gpu->id, 'name' => 'RTX 4070', 'price_type' => 'fixed', 'price_value' => 600],
        ]);

        // ================= RAM =================
        $ram = OptionGroup::create([
            'name' => 'RAM',
            'type' => 'single',
            'is_required' => true,
            'sort_order' => 3,
        ]);

        Option::insert([
            ['option_group_id' => $ram->id, 'name' => '16GB DDR4', 'price_type' => 'fixed', 'price_value' => 80],
            ['option_group_id' => $ram->id, 'name' => '32GB DDR4', 'price_type' => 'fixed', 'price_value' => 140],
            ['option_group_id' => $ram->id, 'name' => '64GB DDR4', 'price_type' => 'fixed', 'price_value' => 260],
        ]);

        // ================= Storage =================
        $storage = OptionGroup::create([
            'name' => 'Storage',
            'type' => 'multiple',
            'is_required' => true,
            'max_selections' => 2,
            'sort_order' => 4,
        ]);

        Option::insert([
            ['option_group_id' => $storage->id, 'name' => '512GB SSD', 'price_type' => 'fixed', 'price_value' => 50],
            ['option_group_id' => $storage->id, 'name' => '1TB SSD', 'price_type' => 'fixed', 'price_value' => 90],
            ['option_group_id' => $storage->id, 'name' => '2TB HDD', 'price_type' => 'fixed', 'price_value' => 70],
        ]);

        // ================= Motherboard =================
        $mb = OptionGroup::create([
            'name' => 'Motherboard',
            'type' => 'single',
            'is_required' => true,
            'sort_order' => 5,
        ]);

        Option::insert([
            ['option_group_id' => $mb->id, 'name' => 'B550 (AMD)', 'price_type' => 'fixed', 'price_value' => 120],
            ['option_group_id' => $mb->id, 'name' => 'X570 (AMD)', 'price_type' => 'fixed', 'price_value' => 200],
            ['option_group_id' => $mb->id, 'name' => 'B660 (Intel)', 'price_type' => 'fixed', 'price_value' => 130],
        ]);

        // ================= PSU =================
        $psu = OptionGroup::create([
            'name' => 'Power Supply',
            'type' => 'single',
            'is_required' => true,
            'sort_order' => 6,
        ]);

        Option::insert([
            ['option_group_id' => $psu->id, 'name' => '550W Bronze', 'price_type' => 'fixed', 'price_value' => 60],
            ['option_group_id' => $psu->id, 'name' => '650W Gold', 'price_type' => 'fixed', 'price_value' => 90],
            ['option_group_id' => $psu->id, 'name' => '750W Gold', 'price_type' => 'fixed', 'price_value' => 120],
        ]);

        // ================= Case =================
        $case = OptionGroup::create([
            'name' => 'Case',
            'type' => 'single',
            'is_required' => true,
            'sort_order' => 7,
        ]);

        Option::insert([
            ['option_group_id' => $case->id, 'name' => 'Mid Tower', 'price_type' => 'fixed', 'price_value' => 70],
            ['option_group_id' => $case->id, 'name' => 'Full Tower', 'price_type' => 'fixed', 'price_value' => 120],
        ]);

        // ================= Cooling =================
        $cooling = OptionGroup::create([
            'name' => 'Cooling',
            'type' => 'single',
            'is_required' => false,
            'sort_order' => 8,
        ]);

        Option::insert([
            ['option_group_id' => $cooling->id, 'name' => 'Air Cooler', 'price_type' => 'fixed', 'price_value' => 40],
            ['option_group_id' => $cooling->id, 'name' => 'Liquid Cooler', 'price_type' => 'fixed', 'price_value' => 120],
        ]);

        // ================= OPTIONAL PRESET =================
        $product = Product::create([
            'name' => 'Gaming PC Preset',
            'slug' => 'gaming-pc',
            'base_price' => 0,
            'is_active' => true,
        ]);

        $product->optionGroups()->attach([
            $cpu->id,
            $gpu->id,
            $ram->id,
            $storage->id,
            $mb->id,
            $psu->id,
            $case->id,
            $cooling->id,
        ]);
    }
}
