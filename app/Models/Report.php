<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'complain_id',
        'type_id',
        'description',
        'status',
        'reported_at',
        'reviewed_at',
        'decision_reason',
        'reviewed_by',
    ];

    protected $casts = [
        'type_id' => 'integer',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function complain()
    {
        return $this->belongsTo(Complain::class);
    }
    public function type()
    {
        return $this->belongsTo(ReportType::class, 'type_id');
    }
}
