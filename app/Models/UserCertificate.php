<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class UserCertificate extends Model
{
      protected $fillable =
       ['user_skill_id','file_path','status','rejection_reason'];

    protected $casts = [
        'status' => 'string',
    ];
    protected function filePath(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value
                ? (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')
                    ? $value
                    : Storage::disk('public')->url($value))
                : null,
        );
    }
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            UserSkill::class,
            'id',        // FK في user_skills
            'id',        // FK في users
            'user_skill_id',  // FK في user_certificates
            'user_id'    // FK في user_skills
        );
    }

    public function userSkill()
    {
        return $this->belongsTo(UserSkill::class);
    }

    // UserCertificate.php
    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
