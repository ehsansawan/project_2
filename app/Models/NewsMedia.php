<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsMedia extends Model
{
     protected $fillable = [
        'news_id',
        'file_path',
        'media_type'
    ];

    protected $casts = [
        'media_type' => 'string',
    ];

    public function news()
    {
        return $this->belongsTo(News::class);
    }
}
