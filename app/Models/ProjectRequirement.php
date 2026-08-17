<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRequirement extends Model
{
    protected $fillable = [
        'project_id', 'skill_name', 'skill_type', 'is_need_certificate',
    ];

    protected $casts = [
        'skill_type' => 'string',
        'is_need_certificate' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
