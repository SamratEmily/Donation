<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'campaign_id',
        'donor_name',
        'donor_email',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'message'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($donation) {
            if ($donation->status === 'completed') {
                $donation->updateCampaignTotal();
            }
        });
        
        static::updated(function ($donation) {
            if ($donation->wasChanged('status')) {
                $donation->updateCampaignTotal();
            }
        });
        
        static::deleted(function ($donation) {
            if ($donation->status === 'completed') {
                $donation->updateCampaignTotal();
            }
        });
    }
    
    public function updateCampaignTotal()
    {
        $campaign = $this->campaign;
        $totalDonations = $campaign->donations()->where('status', 'completed')->sum('amount');
        $campaign->update(['current_amount' => $totalDonations]);
    }
}
