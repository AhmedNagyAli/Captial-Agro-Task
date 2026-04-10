<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\ConfigurationItem;
use App\Models\Option;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class ConfigurationService
{
   
    // Create a new configuration
    
    public function createConfiguration(): Configuration
    {
        return Configuration::create([
            'session_id' => Session::getId(),
            'token' => Str::uuid(),
            'total_price' => 0,
        ]);
    }

    
    // Select an option for a configuration
     
    public function selectOption(Configuration $configuration, Option $option, int $quantity = 1): Configuration
    {
        // Remove old selection in same group
        $configuration->items()
            ->where('option_group_id', $option->option_group_id)
            ->delete();

        // Add new selection with quantity
        ConfigurationItem::create([
            'configuration_id' => $configuration->id,
            'option_group_id' => $option->option_group_id,
            'option_id' => $option->id,
            'option_group_name' => $option->optionGroup->name,
            'option_name' => $option->name,
            'price' => $option->calculatePrice(),
            'quantity' => $quantity,
        ]);

        // Reload items and recalculate total
        $configuration->load('items');
        $configuration->recalculateTotal();

        return $configuration->fresh();
    }

    
    // Clear all items from a configuration
    
    public function clearConfiguration(Configuration $configuration): Configuration
    {
        $configuration->items()->delete();
        $configuration->update(['total_price' => 0]);
        
        return $configuration->fresh();
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