<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreRule extends Model
{
    protected $fillable = [
        'name', 'type', 'points'
    ];

    protected $casts = [
        'type' => 'string',
        'points' => 'integer',
    ];
    
     public function scoreLogs()
    {
        return $this->hasMany(ScoreLog::class, 'reason_rule_id');
    }
}
