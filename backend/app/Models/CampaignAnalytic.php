<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CampaignAnalytic extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'campaign_id',
        'metric_type',
        'source',
        'user_agent',
        'ip_address'
    ];

    protected $dates = [
        'created_at'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopeViews($query)
    {
        return $query->where('metric_type', 'view');
    }

    public function scopeShares($query)
    {
        return $query->where('metric_type', 'share');
    }

    public function scopeClicks($query)
    {
        return $query->where('metric_type', 'click');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    public static function track($campaignId, $metricType, $source = null, $userAgent = null, $ipAddress = null)
    {
        return static::create([
            'campaign_id' => $campaignId,
            'metric_type' => $metricType,
            'source' => $source,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress
        ]);
    }
}
