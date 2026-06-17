<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseProperty extends Model
{
     protected $fillable = [
        'license_id', 'property_id', 'status', 'issue_date', 'expiry_date'
    ];

    protected $casts = [
        'status' => 'string',
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function license()
    {
        return $this->belongsTo(License::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function owners()
    {
        return $this->hasMany(UserLicenseProperty::class);
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_license_properties');
    }
}
