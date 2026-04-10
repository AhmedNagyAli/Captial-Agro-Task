<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\ConfigurationItem;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConfigurationController extends Controller
{
    public function store()
    {
        $config = Configuration::create([
            'session_id' => session()->getId(),
            'token' => Str::uuid(),
            'total_price' => 0,
        ]);

        return response()->json([
            'config_id' => $config->id
        ]);
    }

    public function select(Request $request)
    {
        $request->validate([
            'config_id' => 'required|exists:configurations,id',
            'option_id' => 'required|exists:options,id',
            'quantity' => 'sometimes|integer|min:1|max:99' // Add quantity validation
        ]);

        $config = Configuration::findOrFail($request->config_id);
        $option = Option::with('optionGroup')->findOrFail($request->option_id);
        $quantity = $request->input('quantity', 1);

        // remove old selection in same group
        $config->items()
            ->where('option_group_id', $option->option_group_id)
            ->delete();

        // add new with quantity
        ConfigurationItem::create([
            'configuration_id' => $config->id,
            'option_group_id' => $option->option_group_id,
            'option_id' => $option->id,
            'option_group_name' => $option->optionGroup->name,
            'option_name' => $option->name,
            'price' => $option->calculatePrice(),
            'quantity' => $quantity,
        ]);

        $config->load('items');
        $config->recalculateTotal();

        return response()->json([
            'total' => $config->total_price,
            'items' => $config->items // Return items for better UI state
        ]);
    }
}
