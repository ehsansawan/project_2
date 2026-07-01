<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complain extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'category_id',
        'status',
        'priority_score',
        'pin_id',
    ];

    protected $casts = [
        'type' => 'string',
        'category_id' => 'integer',
        'status' => 'string',
        'priority_score' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pin()
    {
        return $this->belongsTo(MapPin::class, 'pin_id');
    }

    public function photos()
    {
        return $this->hasMany(ComplainMedia::class);
    }

    public function endorsements()
    {
        return $this->hasMany(ComplainEndorsement::class);
    }

    public function contentFlags()
    {
        return $this->hasMany(Report::class, 'complain_id');
    }
    public function category()
    {
        return $this->belongsTo(ComplainCategory::class, 'category_id');
    }
    public function media()
    {
        return $this->hasMany(ComplainMedia::class);
    }
}
