<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function show(int $configId): JsonResponse
    {
        try {
            $config = $this->configurationService->getConfigurationWithItems($configId);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch configuration'
            ], 500);
        }
    }

    public function selectOption(Request $request, int $configId): JsonResponse
    {
        $request->validate([
            'option_id' => 'required|exists:options,id',
            'quantity' => 'sometimes|integer|min:1'
        ]);

        try {
            $config = $this->configurationService->getConfigurationWithItems($configId);
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration not found'
                ], 404);
            }

            $config = $this->configurationService->selectOption(
                $config,
                $request->option_id,
                $request->quantity ?? 1
            );

            return response()->json([
                'success' => true,
                'total' => $config->total_price,
                'items' => $config->items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to select option'
            ], 500);
        }
    }

    public function clear(int $configId): JsonResponse
    {
        try {
            $config = $this->configurationService->getConfigurationWithItems($configId);
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration not found'
                ], 404);
            }

            $config = $this->configurationService->clearConfiguration($config);

            return response()->json([
                'success' => true,
                'total' => $config->total_price,
                'items' => $config->items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear configuration'
            ], 500);
        }
    }

    public function summary(int $configId): JsonResponse
    {
        try {
            $config = $this->configurationService->getConfigurationWithItems($configId);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_items' => $config->items->count(),
                    'total_price' => $config->total_price,
                    'items' => $config->items
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch summary'
            ], 500);
        }
    }


}