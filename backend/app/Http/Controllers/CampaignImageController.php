<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class CampaignImageController extends Controller
{
    /**
     * Upload images for a campaign.
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
                'message' => 'Unauthorized to upload images for this campaign'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'is_primary' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check current image count
        $currentImageCount = $campaign->images()->count();
        $newImageCount = count($request->file('images'));
        
        if ($currentImageCount + $newImageCount > 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 5 images allowed per campaign'
            ], 400);
        }

        $uploadedImages = [];
        $basePath = "campaigns/{$campaignId}/images";

        foreach ($request->file('images') as $index => $file) {
            try {
                // Generate unique filename
                $filename = time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                
                // Store original image
                $file->storeAs($basePath, $filename, 'public');
                
                // Create and store thumbnail
                $this->createThumbnail($file, $basePath, $filename);
                
                // Create database record
                $image = CampaignImage::create([
                    'campaign_id' => $campaignId,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'is_primary' => $index === 0 && $request->get('is_primary', false)
                ]);

                $uploadedImages[] = $image;

            } catch (\Exception $e) {
                // Clean up any uploaded files on error
                Storage::disk('public')->delete($basePath . '/' . $filename);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $uploadedImages,
            'message' => 'Images uploaded successfully'
        ], 201);
    }

    /**
     * Set an image as primary for the campaign.
     */
    public function setPrimary(Request $request, string $campaignId, string $imageId)
    {
        $campaign = Campaign::find($campaignId);
        $image = CampaignImage::find($imageId);

        if (!$campaign || !$image || $image->campaign_id != $campaignId) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign or image not found'
            ], 404);
        }

        $user = $request->user();
        
        // Check authorization
        if (!$user || (!$user->isAdmin() && $campaign->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify this campaign'
            ], 403);
        }

        // Unset current primary image
        CampaignImage::where('campaign_id', $campaignId)
                     ->where('is_primary', true)
                     ->update(['is_primary' => false]);

        // Set new primary image
        $image->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'data' => $image,
            'message' => 'Primary image updated successfully'
        ]);
    }

    /**
     * Delete a campaign image.
     */
    public function destroy(Request $request, string $campaignId, string $imageId)
    {
        $campaign = Campaign::find($campaignId);
        $image = CampaignImage::find($imageId);

        if (!$campaign || !$image || $image->campaign_id != $campaignId) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign or image not found'
            ], 404);
        }

        $user = $request->user();
        
        // Check authorization
        if (!$user || (!$user->isAdmin() && $campaign->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete images from this campaign'
            ], 403);
        }

        $image->delete(); // This will trigger the model's deleted event to clean up files

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }

    /**
     * Get all images for a campaign.
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

        $images = $campaign->images()->orderBy('is_primary', 'desc')->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    /**
     * Create thumbnail for uploaded image.
     */
    private function createThumbnail($file, $basePath, $filename)
    {
        try {
            $thumbnailPath = $basePath . '/thumbnails';
            $pathInfo = pathinfo($filename);
            $thumbnailName = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];

            // Create thumbnail using Intervention Image
            $image = Image::make($file);
            $image->fit(300, 200, function ($constraint) {
                $constraint->upsize();
            });

            // Save thumbnail
            Storage::disk('public')->put(
                $thumbnailPath . '/' . $thumbnailName,
                $image->encode()
            );

        } catch (\Exception $e) {
            // Log error but don't fail the upload
            \Log::error('Failed to create thumbnail: ' . $e->getMessage());
        }
    }
}