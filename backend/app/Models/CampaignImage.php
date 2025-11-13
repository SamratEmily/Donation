<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CampaignImage extends Model
{
    protected $fillable = [
        'campaign_id',
        'filename',
        'original_name',
        'file_size',
        'mime_type',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getUrlAttribute()
    {
        return Storage::url('campaigns/' . $this->campaign_id . '/images/' . $this->filename);
    }

    public function getThumbnailUrlAttribute()
    {
        $pathInfo = pathinfo($this->filename);
        $thumbnailName = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        return Storage::url('campaigns/' . $this->campaign_id . '/images/thumbnails/' . $thumbnailName);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($image) {
            // If this is set as primary, unset other primary images for the same campaign
            if ($image->is_primary) {
                static::where('campaign_id', $image->campaign_id)
                      ->where('is_primary', true)
                      ->update(['is_primary' => false]);
            }
        });
        
        static::updating(function ($image) {
            // If this is set as primary, unset other primary images for the same campaign
            if ($image->is_primary && $image->isDirty('is_primary')) {
                static::where('campaign_id', $image->campaign_id)
                      ->where('id', '!=', $image->id)
                      ->where('is_primary', true)
                      ->update(['is_primary' => false]);
            }
        });
        
        static::deleted(function ($image) {
            // Clean up files when image is deleted
            $basePath = 'campaigns/' . $image->campaign_id . '/images/';
            Storage::delete($basePath . $image->filename);
            
            // Delete thumbnail if exists
            $pathInfo = pathinfo($image->filename);
            $thumbnailName = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            Storage::delete($basePath . 'thumbnails/' . $thumbnailName);
        });
    }
}
