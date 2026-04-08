<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueImage extends Model
{
    public $timestamps = false;
    protected $fillable = ['issue_id', 'image_url', 'created_at'];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}