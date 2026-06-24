<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoreLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'score_rule_id',
        'point_change',
        'type',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
       public function scoreRule()
    {
        return $this->belongsTo(ScoreRule::class, 'score_rule_id');
    }
}
