<?php

namespace App\Http\Controllers;

use App\Models\CampaignCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with campaign counts.
     */
    public function index()
    {
        $categories = CampaignCategory::withCount(['campaigns' => function ($query) {
            $query->where('is_active', true);
        }])->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created category (admin only).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:campaign_categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category = CampaignCategory::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Category created successfully'
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(string $id)
    {
        $category = CampaignCategory::withCount(['campaigns' => function ($query) {
            $query->where('is_active', true);
        }])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update the specified category (admin only).
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required'
            ], 403);
        }

        $category = CampaignCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:campaign_categories,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Category updated successfully'
        ]);
    }

    /**
     * Remove the specified category (admin only).
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required'
            ], 403);
        }

        $category = CampaignCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        // Check if category has campaigns
        $campaignCount = $category->campaigns()->count();
        if ($campaignCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category. It has {$campaignCount} associated campaigns."
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}
