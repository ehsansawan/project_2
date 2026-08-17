<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRequirement extends Model
{
    protected $fillable = [
        'project_id', 'skill_name', 'skill_type', 'required_count', 'is_need_certificate',
    ];

    protected $casts = [
        'skill_type' => 'string',
        'required_count' => 'integer',
        'is_need_certificate' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function participants()
    {
        return $this->hasMany(ProjectParticipant::class);
    }
}
