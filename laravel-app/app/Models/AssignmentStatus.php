<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentStatus extends Model
{
    protected $table = 'assignment_status';
    protected $fillable = ['name'];

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'status_id');
    }
}