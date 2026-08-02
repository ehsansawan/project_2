<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
     protected $fillable = [
        'name', 'prefix', 'qr_code_string', 'estimated_time_minutes', 'status'
    ];

    protected $casts = [
        'estimated_time_minutes' => 'integer',
        'status' => 'string',
    ];

    public function tickets()
    {
        return $this->hasMany(QueueTicket::class, 'service_id');
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'employee_services', 'service_id', 'employee_id')
                    ->withPivot('is_active', 'assigned_at')
                    ->withTimestamps();
    }
}
