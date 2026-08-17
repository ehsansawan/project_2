<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectParticipant extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'role', 'status',
        'whatsapp_number', 'project_requirement_id',
        'approved_by', 'rejected_by', 'approved_at', 'rejected_at',
    ];

    protected $casts = [
        'role' => 'string',
        'status' => 'string',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requirement()
    {
        return $this->belongsTo(ProjectRequirement::class, 'project_requirement_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
