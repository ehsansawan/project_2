<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
      use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'type', 'budget', 'is_votable', 'is_voluntary',
        'is_donation', 'latitude', 'longitude',
        'status', 'rejection_reason', 'start_date', 'end_date',
        'voting_ends_at', 'voting_closed_at',
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
        'voting_ends_at' => 'datetime',
        'voting_closed_at' => 'datetime',
    ];

    protected $appends = ['voting_status'];

    /**
     * Derived, never persisted: recomputed from is_votable/status/voting_ends_at/voting_closed_at
     * on every access so it can never drift out of sync with those columns.
     */
    protected function votingStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_votable) {
                    return 'not_applicable';
                }

                if ($this->voting_closed_at !== null) {
                    return 'force_closed';
                }

                if ($this->voting_ends_at !== null && now()->greaterThanOrEqualTo($this->voting_ends_at)) {
                    return 'expired';
                }

                return 'active';
            }
        );
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where(function (Builder $submitted) {
                $submitted->where('is_votable', true)->where('status', ProjectStatus::Submitted);
            })->orWhereIn('status', [
                ProjectStatus::Approved,
                ProjectStatus::Open,
                ProjectStatus::Active,
                ProjectStatus::Completed,
            ]);
        });
    }

    public function scopeVotable(Builder $query): Builder
    {
        return $query->where('is_votable', true)->where('status', ProjectStatus::Submitted);
    }

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
