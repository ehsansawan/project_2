<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMedia extends Model
{
      protected $fillable = [
        'project_id', 'file_path', 'media_type'
    ];

    protected $casts = [
        'media_type' => 'string',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
