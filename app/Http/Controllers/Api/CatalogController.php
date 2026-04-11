<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OptionGroup;
use App\Models\Option;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    public function getGroups(): JsonResponse
    {
        $groups = Cache::remember('api.catalog.groups', 3600, function () {
            return OptionGroup::with('options')->ordered()->get();
        });

        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    public function getAllOptions(): JsonResponse
    {
        $options = Cache::remember('api.catalog.options', 3600, function () {
            return Option::with('optionGroup')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    public function getGroupOptions(int $groupId): JsonResponse
    {
        $group = OptionGroup::with('options')->find($groupId);

        if (!$group) {
            return response()->json([
                'success' => false,
                'error' => 'Group not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $group
        ]);
    }
}
