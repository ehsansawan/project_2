<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
      use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'description', 'type', 'budget', 'is_votable', 'is_voluntary',
        'is_donation', 'latitude', 'longitude',
        'status', 'rejection_reason', 'start_date', 'end_date'
    ];

    protected $casts = [
        'type' => 'string',
        'is_votable' => 'boolean',
        'is_voluntary' => 'boolean',
        'is_donation' => 'boolean',
        'status' => ProjectStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'user_id' => 'integer',
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function requirements()
    {
        return $this->hasMany(ProjectRequirement::class);
    }
}
