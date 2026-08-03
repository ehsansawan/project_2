<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationRejections extends Model
{
    //
    use softDeletes;
    protected $fillable=['user_id','verification_request_id','reason','description'];

    protected $casts = [
        'reason' => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function verification_request()
    {
        return $this->belongsTo(VerificationRequests::class);
    }
}
