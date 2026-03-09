<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceCategoryController extends Controller
{
    // List all active categories with their services
    public function index(Request $request): JsonResponse
    {
        $categories = ServiceCategory::with(['activeServices'])
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json([
            'data' => $categories->map(function ($category) {
                return [
                    'id'          => (string) $category->id,
                    'name'        => $category->name,
                    'slug'        => $category->slug,
                    'description' => $category->description,
                    'services'    => $category->activeServices->map(function ($service) {
                        return [
                            'id'          => (string) $service->id,
                            'name'        => $service->name,
                            'slug'        => $service->slug,
                            'description' => $service->description,
                        ];
                    }),
                ];
            }),
        ], 200);
    }

    // Show a single category with its services
    public function show(Request $request, string $id): JsonResponse
    {
        $category = ServiceCategory::with(['activeServices'])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id'          => (string) $category->id,
                'name'        => $category->name,
                'slug'        => $category->slug,
                'description' => $category->description,
                'services'    => $category->activeServices->map(function ($service) {
                    return [
                        'id'          => (string) $service->id,
                        'name'        => $service->name,
                        'slug'        => $service->slug,
                        'description' => $service->description,
                    ];
                }),
            ],
        ], 200);
    }
}