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
        $campaigns = Campaign::with('donations')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

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

        $campaign = Campaign::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $campaign,
            'message' => 'Campaign created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
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

        $campaign->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $campaign,
            'message' => 'Campaign updated successfully'
        ]);
    }

    /**
     * Close/deactivate a campaign
     */
    public function close(string $id)
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $campaign->closeCampaign();

        return response()->json([
            'success' => true,
            'data' => $campaign,
            'message' => 'Campaign closed successfully'
        ]);
    }

    /**
     * Reopen/reactivate a campaign
     */
    public function reopen(string $id)
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $campaign->reopenCampaign();

        return response()->json([
            'success' => true,
            'data' => $campaign,
            'message' => 'Campaign reopened successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully'
        ]);
    }
}
