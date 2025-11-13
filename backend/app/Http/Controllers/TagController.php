<?php

namespace App\Http\Controllers;

use App\Models\CampaignTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    /**
     * Display a listing of tags with search and autocomplete.
     */
    public function index(Request $request)
    {
        $query = CampaignTag::query();

        // Search functionality for autocomplete
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // Limit results for autocomplete
        $limit = $request->get('limit', 20);
        $tags = $query->orderBy('usage_count', 'desc')
                     ->orderBy('name')
                     ->limit($limit)
                     ->get();

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Get most popular/used tags.
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $tags = CampaignTag::where('usage_count', '>', 0)
                          ->orderBy('usage_count', 'desc')
                          ->orderBy('name')
                          ->limit($limit)
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:campaign_tags'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $tag = CampaignTag::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'data' => $tag,
            'message' => 'Tag created successfully'
        ], 201);
    }

    /**
     * Find or create tags by names.
     */
    public function findOrCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'names' => 'required|array',
            'names.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $tags = [];
        foreach ($request->names as $name) {
            $tag = CampaignTag::firstOrCreate(
                ['name' => trim($name)],
                ['usage_count' => 0]
            );
            $tags[] = $tag;
        }

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Display the specified tag.
     */
    public function show(string $id)
    {
        $tag = CampaignTag::withCount(['campaigns' => function ($query) {
            $query->where('is_active', true);
        }])->find($id);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    /**
     * Update the specified tag (admin only).
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

        $tag = CampaignTag::find($id);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:campaign_tags,name,' . $id
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $tag->update($request->only(['name']));

        return response()->json([
            'success' => true,
            'data' => $tag,
            'message' => 'Tag updated successfully'
        ]);
    }

    /**
     * Remove the specified tag (admin only).
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

        $tag = CampaignTag::find($id);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        // Detach from all campaigns before deleting
        $tag->campaigns()->detach();
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully'
        ]);
    }
}
