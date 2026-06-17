<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
      use SoftDeletes;

    protected $fillable = [
        'number', 'status', 'ownership', 'type', 'address_details', 'pin_id'
    ];

    protected $casts = [
        'status' => 'string',
        'ownership' => 'string',
        'type' => 'string',
    ];

    public function pin()
    {
        return $this->belongsTo(MapPin::class);
    }

    public function licenses()
    {
        return $this->belongsToMany(License::class, 'license_properties')
                    ->withPivot('status', 'issue_date', 'expiry_date')
                    ->withTimestamps();
    }

    public function licenseProperties()
    {
        return $this->hasMany(LicenseProperty::class);
    }
}
