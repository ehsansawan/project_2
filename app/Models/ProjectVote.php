<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectVote extends Model
{
     protected $fillable = [
        'project_id', 'user_id', 'value', 'citizenship_score_at_vote_time', 'vote_weight',
    ];

    protected $casts = [
        'value' => 'boolean',
        'citizenship_score_at_vote_time' => 'integer',
        'vote_weight' => 'decimal:4',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
