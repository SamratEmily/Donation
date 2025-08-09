<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $campaigns = Campaign::with(['donations', 'user'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }

    /**
     * Display all campaigns for admin (including inactive ones).
     */
    public function all(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        if ($user->isAdmin()) {
            // Admin can see all campaigns
            $campaigns = Campaign::with(['donations', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Regular users can only see their own campaigns
            $campaigns = Campaign::with(['donations', 'user'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'creator_name' => 'required|string|max:255',
            'creator_email' => 'required|email|max:255',
            'target_amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:bkash,nagad,rocket,bank'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $campaignData = $request->all();
        
        // Add user_id if authenticated
        if ($request->user()) {
            $campaignData['user_id'] = $request->user()->id;
        }

        $campaign = Campaign::create($campaignData);

        return response()->json([
            'success' => true,
            'data' => $campaign->load('user'),
            'message' => 'Campaign created successfully'
        ], 201);
    }

    /**
     * Display the specified resource by ID.
     */
    public function show(string $id)
    {
        $campaign = Campaign::with(['donations' => function($query) {
            $query->where('status', 'completed')->orderBy('created_at', 'desc');
        }])->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign
        ]);
    }

    /**
     * Display the specified resource by slug.
     */
    public function showBySlug(string $slug)
    {
        $campaign = Campaign::with(['donations' => function($query) {
            $query->where('status', 'completed')->orderBy('created_at', 'desc');
        }])->where('slug', $slug)->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $user = $request->user();
        
        // Check authorization
        if (!$user || (!$user->isAdmin() && $campaign->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this campaign'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'string',
            'target_amount' => 'numeric|min:0',
            'payment_type' => 'in:bkash,nagad,rocket,bank',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Only admin can change is_active status
        $updateData = $request->all();
        if (!$user->isAdmin() && isset($updateData['is_active'])) {
            unset($updateData['is_active']);
        }

        $campaign->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $campaign->load('user'),
            'message' => 'Campaign updated successfully'
        ]);
    }



    /**
     * Toggle campaign status (admin only)
     */
    public function toggleStatus(Request $request, string $id)
    {
        $user = $request->user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required'
            ], 403);
        }

        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $campaign->update(['is_active' => !$campaign->is_active]);

        return response()->json([
            'success' => true,
            'data' => $campaign->load('user'),
            'message' => 'Campaign status updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $user = $request->user();
        
        // Check authorization
        if (!$user || (!$user->isAdmin() && $campaign->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this campaign'
            ], 403);
        }

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully'
        ]);
    }
}
