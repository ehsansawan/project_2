<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class VerificationImages extends Model
{
    //
    use softDeletes;
    protected $fillable=['verification_request_id','image_url'];


    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value
                ? (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')
                    ? $value
                    : Storage::disk('public')->url($value))
                : null,
        );
    }

    public function verification_request()
    {
        return $this->belongsTo(VerificationRequests::class);
    }

}
