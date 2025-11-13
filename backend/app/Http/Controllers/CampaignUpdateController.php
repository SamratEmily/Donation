<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CampaignUpdateController extends Controller
{
    /**
     * Get all updates for a campaign.
     */
    public function index(string $campaignId)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $updates = $campaign->updates()
                           ->published()
                           ->recent()
                           ->get();

        return response()->json([
            'success' => true,
            'data' => $updates
        ]);
    }

    /**
     * Store a new campaign update.
     */
    public function store(Request $request, string $campaignId)
    {
        $campaign = Campaign::find($campaignId);

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
                'message' => 'Unauthorized to create updates for this campaign'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'is_published' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [
            'campaign_id' => $campaignId,
            'title' => $request->title,
            'content' => $request->content,
            'is_published' => $request->get('is_published', true)
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $filename = time() . '_update.' . $file->getClientOriginalExtension();
                $path = "campaigns/{$campaignId}/updates/{$filename}";
                
                $file->storeAs('campaigns/' . $campaignId . '/updates', $filename, 'public');
                $updateData['image_path'] = $path;

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image: ' . $e->getMessage()
                ], 500);
            }
        }

        $update = CampaignUpdate::create($updateData);

        // TODO: Send notification emails to donors when update is published
        if ($update->is_published) {
            $this->notifyDonors($campaign, $update);
        }

        return response()->json([
            'success' => true,
            'data' => $update,
            'message' => 'Campaign update created successfully'
        ], 201);
    }

    /**
     * Display a specific update.
     */
    public function show(string $campaignId, string $updateId)
    {
        $campaign = Campaign::find($campaignId);
        $update = CampaignUpdate::find($updateId);

        if (!$campaign || !$update || $update->campaign_id != $campaignId) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign or update not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $update
        ]);
    }

    /**
     * Update a campaign update.
     */
    public function update(Request $request, string $campaignId, string $updateId)
    {
        $campaign = Campaign::find($campaignId);
        $update = CampaignUpdate::find($updateId);

        if (!$campaign || !$update || $update->campaign_id != $campaignId) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign or update not found'
            ], 404);
        }

        $user = $request->user();
        
        // Check authorization
        if (!$user || (!$user->isAdmin() && $campaign->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this campaign update'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'content' => 'string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_published' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only(['title', 'content', 'is_published']);

        // Handle new image upload
        if ($request->hasFile('image')) {
            try {
                // Delete old image if exists
                if ($update->image_path) {
                    Storage::disk('public')->delete($update->image_path);
                }

                $file = $request->file('image');
                $filename = time() . '_update.' . $file->getClientOriginalExtension();
                $path = "campaigns/{$campaignId}/updates/{$filename}";
                
                $file->storeAs('campaigns/' . $campaignId . '/updates', $filename, 'public');
                $updateData['image_path'] = $path;

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image: ' . $e->getMessage()
                ], 500);
            }
        }

        $wasPublished = $update->is_published;
        $update->update($updateData);

        // Send notifications if newly published
        if (!$wasPublished && $update->is_published) {
            $this->notifyDonors($campaign, $update);
        }

        return response()->json([
            'success' => true,
            'data' => $update,
            'message' => 'Campaign update updated successfully'
        ]);
    }

    /**
     * Delete a campaign update.
     */
    public function destroy(Request $request, string $campaignId, string $updateId)
    {
        $campaign = Campaign::find($campaignId);
        $update = CampaignUpdate::find($updateId);

        if (!$campaign || !$update || $update->campaign_id != $campaignId) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign or update not found'
            ], 404);
        }

        $user = $request->user();
        
        // Check authorization
        if (!$user || (!$user->isAdmin() && $campaign->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this campaign update'
            ], 403);
        }

        $update->delete(); // This will trigger the model's deleted event to clean up files

        return response()->json([
            'success' => true,
            'message' => 'Campaign update deleted successfully'
        ]);
    }

    /**
     * Notify donors about new campaign update.
     * TODO: Implement email notification system
     */
    private function notifyDonors(Campaign $campaign, CampaignUpdate $update)
    {
        // Get unique donor emails from completed donations
        $donorEmails = $campaign->donations()
                               ->where('status', 'completed')
                               ->whereNotNull('donor_email')
                               ->distinct()
                               ->pluck('donor_email')
                               ->toArray();

        // TODO: Queue email notifications
        // This would typically use Laravel's queue system and mail notifications
        \Log::info("Campaign update notification needed for " . count($donorEmails) . " donors", [
            'campaign_id' => $campaign->id,
            'update_id' => $update->id,
            'donor_count' => count($donorEmails)
        ]);
    }
}