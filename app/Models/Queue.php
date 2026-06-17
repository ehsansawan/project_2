<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
     protected $fillable = [
        'name', 'qr_code_string', 'employee_id', 'estimated_time_minutes', 'status'
    ];

    protected $casts = [
        'status' => 'string',
        'estimated_time_minutes' => 'integer',
    ];

   
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

 
    public function virtualUsers()
    {
        return $this->hasMany(VirtualUser::class);
    }
}
