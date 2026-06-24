<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable ,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'national_id',
        'password',
        'birth_date',
        'privacy_policy_accepted',
        'terms_of_service_accepted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function scoreLogs()
    {
        return $this->hasMany(ScoreLog::class)->latest();
    }

     public function userSkills()
    {
        return $this->hasMany(UserSkill::class);
    }

    public function userCertificates()
    {
        return $this->hasManyThrough(UserCertificate::class, UserSkill::class);
    }
    public function licenseProperties()
    {
        return $this->belongsToMany(LicenseProperty::class, 'user_license_properties');
    }
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
    public function donations()
    {
        return $this->hasMany(Donation::class);
    }
}
