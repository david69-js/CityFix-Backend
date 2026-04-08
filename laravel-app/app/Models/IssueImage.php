<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class IssueImage extends Model
{
    public $timestamps = false;

    protected $table = 'issue_images';

    protected $fillable = [
        'issue_id',
        'image_url',
    ];

    protected $appends = [
        'full_url',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function getFullUrlAttribute(): string
    {
        if (
            str_starts_with($this->image_url, 'http://') ||
            str_starts_with($this->image_url, 'https://')
        ) {
            return $this->image_url;
        }

        return Storage::url($this->image_url);
    }
}