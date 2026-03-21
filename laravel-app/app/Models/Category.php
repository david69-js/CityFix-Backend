<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['name', 'icon', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class , 'parent_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class , 'parent_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}