<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'complain_id',
        'type',
        'description',
        'status',
    ];

    protected $casts = [
        'type' => 'string',
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
}
