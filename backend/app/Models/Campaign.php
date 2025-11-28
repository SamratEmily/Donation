<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaign extends Model
{
    protected $fillable = [
        'title',
        'description',
        'creator_name',
        'creator_email',
        'creator_phone',
        'target_amount',
        'current_amount',
        'payment_type',
        'slug',
        'is_active',
        'status',
        'user_id'
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    protected $appends = ['progress_percentage', 'is_completed'];

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->target_amount == 0) return 0;
        return ($this->current_amount / $this->target_amount) * 100;
    }
    
    public function getIsCompletedAttribute()
    {
        return $this->current_amount >= $this->target_amount;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($campaign) {
            if (empty($campaign->slug)) {
                $campaign->slug = Str::slug($campaign->title) . '-' . Str::random(6);
            }
        });
    }
}
