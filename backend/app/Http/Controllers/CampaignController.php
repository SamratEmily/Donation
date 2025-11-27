<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use Illuminate\Http\Request;

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

        return CampaignResource::collection($campaigns)->additional([
            'success' => true
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

        return CampaignResource::collection($campaigns)->additional([
            'success' => true
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCampaignRequest $request)
    {
        $campaignData = $request->validated();
        
        // Add user_id if authenticated
        if ($request->user()) {
            $campaignData['user_id'] = $request->user()->id;
        }

        $campaign = Campaign::create($campaignData);

        return (new CampaignResource($campaign->load('user')))
            ->additional([
                'success' => true,
                'message' => 'Campaign created successfully'
            ]);
    }

    /**
     * Display the specified resource by ID.
     */
    public function show(Campaign $campaign)
    {
        $campaign->load(['donations' => function($query) {
            $query->where('status', 'completed')->orderBy('created_at', 'desc');
        }]);

        return (new CampaignResource($campaign))->additional([
            'success' => true
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

        return (new CampaignResource($campaign))->additional([
            'success' => true
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $user = $request->user();
        
        // Check authorization
        if (!$user || (!$user->isAdmin() && $campaign->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this campaign'
            ], 403);
        }

        // Only admin can change is_active status
        $updateData = $request->validated();
        
        // Filter allowed fields if not admin? 
        // The original code didn't explicitly filter based on role for fields, just is_active.
        // But the validator in UpdateCampaignRequest includes is_active.
        // We should probably check if user is admin before allowing is_active change.
        
        if (!$user->isAdmin() && isset($updateData['is_active'])) {
             unset($updateData['is_active']);
        }

        $campaign->update($updateData);

        return (new CampaignResource($campaign->load('user')))
            ->additional([
                'success' => true,
                'message' => 'Campaign updated successfully'
            ]);
    }



    /**
     * Toggle campaign status (owner or admin)
     */
    public function toggleStatus(Request $request, Campaign $campaign)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Check if user is admin or campaign owner
        if (!$user->isAdmin() && $campaign->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to toggle this campaign status'
            ], 403);
        }

        $validated = $request->validate([
            'target_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $newStatus = !$campaign->is_active;

        $campaign->is_active = $newStatus;

        if ($newStatus && array_key_exists('target_amount', $validated) && $validated['target_amount'] !== null) {
            $campaign->target_amount = $validated['target_amount'];
        }

        $campaign->save();

        return (new CampaignResource($campaign->load('user')))
            ->additional([
                'success' => true,
                'message' => 'Campaign status updated successfully'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Campaign $campaign)
    {
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
