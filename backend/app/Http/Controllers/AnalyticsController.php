<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignAnalytic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Track campaign interaction (view, share, click).
     */
    public function track(Request $request, string $campaignId)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $metricType = $request->get('type', 'view');
        $source = $request->get('source');
        $userAgent = $request->header('User-Agent');
        $ipAddress = $request->ip();

        // Validate metric type
        if (!in_array($metricType, ['view', 'share', 'click'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid metric type'
            ], 400);
        }

        // Track the interaction
        CampaignAnalytic::track($campaignId, $metricType, $source, $userAgent, $ipAddress);

        // Update campaign counters
        if ($metricType === 'view') {
            $campaign->incrementViewCount();
        } elseif ($metricType === 'share') {
            $campaign->incrementShareCount();
        }

        return response()->json([
            'success' => true,
            'message' => 'Interaction tracked successfully'
        ]);
    }

    /**
     * Get analytics for a specific campaign (owner only).
     */
    public function show(Request $request, string $campaignId)
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
                'message' => 'Unauthorized to view analytics for this campaign'
            ], 403);
        }

        $dateRange = $request->get('range', '30'); // Default 30 days
        $startDate = Carbon::now()->subDays($dateRange);

        // Basic metrics
        $metrics = [
            'total_views' => $campaign->view_count,
            'total_shares' => $campaign->share_count,
            'total_donations' => $campaign->donations()->where('status', 'completed')->count(),
            'total_amount' => $campaign->current_amount,
            'conversion_rate' => $campaign->view_count > 0 ? 
                ($campaign->donations()->where('status', 'completed')->count() / $campaign->view_count) * 100 : 0
        ];

        // Daily analytics for the specified range
        $dailyAnalytics = CampaignAnalytic::where('campaign_id', $campaignId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(CASE WHEN metric_type = "view" THEN 1 END) as views'),
                DB::raw('COUNT(CASE WHEN metric_type = "share" THEN 1 END) as shares'),
                DB::raw('COUNT(CASE WHEN metric_type = "click" THEN 1 END) as clicks')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top referral sources
        $topSources = CampaignAnalytic::where('campaign_id', $campaignId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('source')
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Donation analytics
        $donationAnalytics = $campaign->donations()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'daily_analytics' => $dailyAnalytics,
                'top_sources' => $topSources,
                'donation_analytics' => $donationAnalytics,
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => Carbon::now()->toDateString()
                ]
            ]
        ]);
    }

    /**
     * Get dashboard analytics for user's campaigns.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $dateRange = $request->get('range', '30'); // Default 30 days
        $startDate = Carbon::now()->subDays($dateRange);

        // Get user's campaigns
        $campaignQuery = Campaign::where('user_id', $user->id);
        
        if ($user->isAdmin()) {
            $campaignQuery = Campaign::query(); // Admin sees all campaigns
        }

        $campaigns = $campaignQuery->get();
        $campaignIds = $campaigns->pluck('id');

        // Overall metrics
        $metrics = [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('is_active', true)->count(),
            'total_views' => $campaigns->sum('view_count'),
            'total_shares' => $campaigns->sum('share_count'),
            'total_donations' => DB::table('donations')
                ->whereIn('campaign_id', $campaignIds)
                ->where('status', 'completed')
                ->count(),
            'total_amount_raised' => $campaigns->sum('current_amount'),
            'avg_conversion_rate' => $campaigns->where('view_count', '>', 0)->avg(function ($campaign) {
                return ($campaign->donations()->where('status', 'completed')->count() / $campaign->view_count) * 100;
            })
        ];

        // Top performing campaigns
        $topCampaigns = $campaigns->sortByDesc('view_count')->take(5)->values();

        // Recent analytics trends
        $trends = CampaignAnalytic::whereIn('campaign_id', $campaignIds)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(CASE WHEN metric_type = "view" THEN 1 END) as views'),
                DB::raw('COUNT(CASE WHEN metric_type = "share" THEN 1 END) as shares')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'top_campaigns' => $topCampaigns,
                'trends' => $trends,
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => Carbon::now()->toDateString()
                ]
            ]
        ]);
    }

    /**
     * Export analytics data as CSV.
     */
    public function export(Request $request, string $campaignId)
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
                'message' => 'Unauthorized to export analytics for this campaign'
            ], 403);
        }

        $dateRange = $request->get('range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Get analytics data
        $analytics = CampaignAnalytic::where('campaign_id', $campaignId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        // Generate CSV content
        $csvContent = "Date,Time,Type,Source,IP Address\n";
        foreach ($analytics as $record) {
            $csvContent .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $record->created_at->toDateString(),
                $record->created_at->toTimeString(),
                $record->metric_type,
                $record->source ?? 'Direct',
                $record->ip_address ?? 'Unknown'
            );
        }

        $filename = "campaign_{$campaignId}_analytics_" . date('Y-m-d') . ".csv";

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}