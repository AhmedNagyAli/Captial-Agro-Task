<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\ConfigurationItem;
use App\Models\Option;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class ConfigurationService
{
    // Create a new configuration
    public function createConfiguration(): Configuration
    {
        return Configuration::create([
            'session_id' => Session::getId(),
            'token' => (string) Str::uuid(),
            'total_price' => 0,
        ]);
    }

    // Select an option for a configuration
    public function selectOption(Configuration $configuration, Option $option, int $quantity = 1): Configuration
    {
        DB::transaction(function () use ($configuration, $option, $quantity) {
            // Remove old selection in same group
            $configuration->items()
                ->where('option_group_id', $option->option_group_id)
                ->delete();

            // Calculate the price based on option type
            $price = $this->calculateOptionPrice($option, $quantity);

            ConfigurationItem::create([
                'configuration_id' => $configuration->id,
                'option_group_id' => $option->option_group_id,
                'option_id' => $option->id,
                'option_group_name' => $option->optionGroup->name ?? 'Unknown',
                'option_name' => $option->name,
                'price' => $price,
                'quantity' => $quantity,
            ]);

            // Recalculate total after adding
            $this->recalculateTotal($configuration);
        });

        return $configuration->fresh();
    }

    // Calculate option price based on price type
    private function calculateOptionPrice(Option $option, int $quantity = 1): float
    {
        // For fixed price, just multiply by quantity
        if ($option->price_type === Option::PRICE_TYPE_FIXED) {
            return $option->price_value * $quantity;
        }

        // For now, return 0 
        return 0;
    }

    // Remove an option from configuration
    public function removeOption(Configuration $configuration, int $groupId): Configuration
    {
        DB::transaction(function () use ($configuration, $groupId) {
            $configuration->items()
                ->where('option_group_id', $groupId)
                ->delete();

            $this->recalculateTotal($configuration);
        });

        return $configuration->fresh();
    }

    // Update quantity of an option
    public function updateQuantity(Configuration $configuration, int $groupId, int $quantity): Configuration
    {
        DB::transaction(function () use ($configuration, $groupId, $quantity) {
            $item = $configuration->items()
                ->where('option_group_id', $groupId)
                ->first();

            if ($item) {
                $option = Option::find($item->option_id);
                $newPrice = $this->calculateOptionPrice($option, $quantity);

                $item->update([
                    'quantity' => $quantity,
                    'price' => $newPrice,
                ]);

                $this->recalculateTotal($configuration);
            }
        });

        return $configuration->fresh();
    }

    // Clear all items from a configuration
    public function clearConfiguration(Configuration $configuration): Configuration
    {
        DB::transaction(function () use ($configuration) {
            $configuration->items()->delete();
            $configuration->update(['total_price' => 0]);
        });

        return $configuration->fresh();
    }

    // Recalculate total price
    private function recalculateTotal(Configuration $configuration): void
    {
        $total = $configuration->items()->sum(DB::raw('price * quantity'));
        $configuration->update(['total_price' => $total]);
    }

    // Get configuration with items
    public function getConfigurationWithItems(int $configId): ?Configuration
    {
        return Configuration::with('items')->find($configId);
    }

    // Validate if configuration has items
    public function hasItems(Configuration $configuration): bool
    {
        return $configuration->items()->exists();
    }
}
