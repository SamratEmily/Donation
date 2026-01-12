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
            ->where('status', 'approved')
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

        // Default status is pending
        $campaignData['status'] = 'pending';
        $campaignData['is_active'] = false; // Pending campaigns are not active yet

        $campaign = Campaign::create($campaignData);

        return (new CampaignResource($campaign->load('user')))
            ->additional([
                'success' => true,
                'message' => 'Campaign created successfully and sent for approval'
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
    public function showBySlug(Request $request, string $slug)
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

        $user = $request->user();
        
        // Allow viewing if:
        // 1. Campaign is approved (public access)
        // 2. User is the owner of the campaign
        // 3. User is an admin
        $canView = $campaign->status === 'approved' 
                   || ($user && $user->id === $campaign->user_id)
                   || ($user && $user->isAdmin());

        if (!$canView) {
             return response()->json([
                'success' => false,
                'message' => 'Campaign is not available'
            ], 403);
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

        $updateData = $request->validated();
        
        // Only admin can change status directly via update
        if (!$user->isAdmin()) {
             unset($updateData['status']);
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
     * Update campaign status (admin only)
     */
    public function updateStatus(Request $request, Campaign $campaign)
    {
        $user = $request->user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $campaign->status = $validated['status'];
        
        // Sync is_active with status
        $campaign->is_active = ($validated['status'] === 'approved');

        if (array_key_exists('target_amount', $validated) && $validated['target_amount'] !== null) {
            $campaign->target_amount = $validated['target_amount'];
        }

        $campaign->save();

        return (new CampaignResource($campaign->load('user')))
            ->additional([
                'success' => true,
                'message' => 'Campaign status updated to ' . $campaign->status
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
