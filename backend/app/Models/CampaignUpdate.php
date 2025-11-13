<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CampaignUpdate extends Model
{
    protected $fillable = [
        'campaign_id',
        'title',
        'content',
        'image_path',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return Storage::url($this->image_path);
        }
        return null;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::deleted(function ($update) {
            // Clean up image file when update is deleted
            if ($update->image_path) {
                Storage::delete($update->image_path);
            }
        });
    }
}
