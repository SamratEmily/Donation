<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon'
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'category_id');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
        
        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
