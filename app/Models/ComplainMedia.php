<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
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
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
