<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTicket extends Model
{
    protected $fillable = [
        'user_name', 'current_number', 'service_id', 'status', 
        'is_manual', 'served_by_employee_id', 'latitude', 'longitude',
        'joined_at', 'called_at'
    ];

    protected $casts = [
        'status' => 'string',
        'is_manual' => 'boolean',
        'joined_at' => 'datetime',
        'called_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by_employee_id');
    }
    
    public function getDisplayNumberAttribute(): string
    {
        $prefix = strtoupper($this->service?->prefix ?? 'A');
        $number = str_pad($this->current_number, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$number}";
    }
}
