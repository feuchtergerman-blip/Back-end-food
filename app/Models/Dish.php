<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dish extends Model
{
    protected $fillable = [
        'restaurant_id', 'category_id', 'name', 'description', 
        'price', 'available', 'video_url', 'tags'
    ];

    protected $casts = [
        'tags' => 'array',
        'available' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
