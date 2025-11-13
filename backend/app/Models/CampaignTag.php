<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'usage_count'
    ];

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_tag_pivot', 'tag_id', 'campaign_id')
                    ->withTimestamps();
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function decrementUsage()
    {
        $this->decrement('usage_count');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
        
        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}
