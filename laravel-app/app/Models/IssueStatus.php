<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueStatus extends Model
{
    protected $table = 'issue_status';

    protected $fillable = [
        'name',
        'color',
        'sort_order',
    ];

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'status_id');
    }
}