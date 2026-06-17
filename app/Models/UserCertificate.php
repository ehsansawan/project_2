<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCertificate extends Model
{
      protected $fillable =
       ['user_skill_id','file_path',];

    protected $casts = [
        'is_verified' => 'boolean',
        'issue_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userSkill()
    {
        return $this->belongsTo(UserSkill::class);
    }
}
