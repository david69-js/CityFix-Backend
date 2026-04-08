<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    protected $fillable = ['issue_id', 'worker_id', 'status_id', 'notes', 'assigned_at'];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AssignmentStatus::class, 'status_id');
    }
}