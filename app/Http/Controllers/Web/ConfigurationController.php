<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationSelectRequest;
use App\Services\ConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    protected ConfigurationService $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    // Create a new configuration
    
    public function store(): JsonResponse
    {
        try {
            $config = $this->configurationService->createConfiguration();

            return response()->json([
                'success' => true,
                'config_id' => $config->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create configuration'
            ], 500);
        }
    }

    
    // Select an option for a configuration
    
    public function select(ConfigurationSelectRequest $request): JsonResponse
    {
        try {
            $config = $this->configurationService->getConfigurationWithItems($request->config_id);
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration not found'
                ], 404);
            }

            $config = $this->configurationService->selectOption(
                $config,
                $request->getOption(),
                $request->getQuantity()
            );

            return response()->json([
                'success' => true,
                'total' => $config->total_price,
                'items' => $config->items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to select option: ' . $e->getMessage()
            ], 500);
        }
    }

    
    // Clear all selections from a configuration
    
    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'config_id' => 'required|exists:configurations,id'
        ]);

        try {
            $config = $this->configurationService->getConfigurationWithItems($request->config_id);
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration not found'
                ], 404);
            }

            $config = $this->configurationService->clearConfiguration($config);

            return response()->json([
                'success' => true,
                'message' => 'Configuration cleared successfully',
                'total' => $config->total_price
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear configuration'
            ], 500);
        }
    }
}