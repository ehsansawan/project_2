<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'type', 'status'
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'license_properties')
                    ->withPivot('status', 'issue_date', 'expiry_date')
                    ->withTimestamps();
    }

    public function licenseProperties()
    {
        return $this->hasMany(LicenseProperty::class);
    }
}
