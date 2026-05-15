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

        $path = $this->image_url;

        if (str_starts_with($path, '/storage/')) {
            return Storage::disk('public')->url(substr($path, 9));
        }

        return Storage::disk(env('FILESYSTEM_DISK', 'local') === 'r2' ? 'r2' : 'public')->url($path);
    }
}