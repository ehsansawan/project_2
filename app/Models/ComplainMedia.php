<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplainMedia extends Model
{
     protected $fillable = [
        'complain_id',
        'media_type',
        'file_path',
    ];

    protected $casts = [
        'media_type' => 'string',
    ];

    public function complain()
    {
        return $this->belongsTo(Complain::class);
    }
}
