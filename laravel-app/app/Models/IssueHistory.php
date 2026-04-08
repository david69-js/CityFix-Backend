<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueHistory extends Model
{
    public $timestamps = false;

    protected $table = 'issue_history';

    protected $fillable = [
        'issue_id',
        'status_id',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(IssueStatus::class, 'status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}