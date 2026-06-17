<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLicenseProperty extends Model
{
     protected $fillable = [
        'user_id', 'license_property_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function licenseProperty()
    {
        return $this->belongsTo(LicenseProperty::class);
    }
}
