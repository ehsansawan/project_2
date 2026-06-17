<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
      use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'type', 'budget', 'is_votable', 'is_voluntary', 
        'status', 'start_date', 'end_date'
    ];

    protected $casts = [
        'type' => 'string',
        'is_votable' => 'boolean',
        'is_voluntary' => 'boolean',
        'status' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    public function participants()
    {
        return $this->hasMany(ProjectParticipant::class);
    }

    public function votes()
    {
        return $this->hasMany(ProjectVote::class);
    }

    public function media()
    {
        return $this->hasMany(ProjectMedia::class);
    }
}
