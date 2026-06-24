<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapPin extends Model
{
     protected $fillable = [
        'latitude',
        'longitude',
        'type',
        'description',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'type' => 'string',
        'status' => 'string',
    ];

    public function complains()
    {
        return $this->hasMany(Complain::class, 'pin_id');
    }
}
