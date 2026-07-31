<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationRequests extends Model
{
    //
    use softDeletes;
    protected $fillable = ['user_id','national_id','status','rejection_reason'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(VerificationImages::class,'verification_request_id');
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class,'auditable');
    }

    public function rejections()
    {
        return $this->hasMany(VerificationRejections::class,'verification_request_id');
    }
}
