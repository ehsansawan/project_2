<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualUser extends Model
{
     protected $fillable = [
        'user_name', 'service_type', 'current_number', 'queue_id', 'status', 'joined_at', 'called_at'
    ];

    protected $casts = [
        'status' => 'string',
        'joined_at' => 'datetime',
        'called_at' => 'datetime',
    ];

    
    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }


    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }
}
