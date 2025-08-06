<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DonationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Donation::with('campaign');

        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $donations = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $donations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|exists:campaigns,id',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'nullable|email|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bkash,nagad,rocket,bank',
            'transaction_id' => 'nullable|string|max:255',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $campaign = Campaign::find($request->campaign_id);
        if (!$campaign->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is not active'
            ], 400);
        }

        $donation = Donation::create(array_merge(
            $request->all(),
            ['status' => 'completed'] // In real app, this would be 'pending' until payment confirmation
        ));

        // Campaign amount will be updated automatically via the Donation model boot method

        return response()->json([
            'success' => true,
            'data' => $donation->load('campaign'),
            'message' => 'Donation processed successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $donation = Donation::with('campaign')->find($id);

        if (!$donation) {
            return response()->json([
                'success' => false,
                'message' => 'Donation not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $donation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $donation = Donation::find($id);

        if (!$donation) {
            return response()->json([
                'success' => false,
                'message' => 'Donation not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'in:pending,completed,failed',
            'transaction_id' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $donation->update($request->all());

        // Campaign amount will be updated automatically via the Donation model boot method

        return response()->json([
            'success' => true,
            'data' => $donation->load('campaign'),
            'message' => 'Donation updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $donation = Donation::find($id);

        if (!$donation) {
            return response()->json([
                'success' => false,
                'message' => 'Donation not found'
            ], 404);
        }

        $donation->delete();

        // Campaign amount will be updated automatically via the Donation model boot method

        return response()->json([
            'success' => true,
            'message' => 'Donation deleted successfully'
        ]);
    }
}
