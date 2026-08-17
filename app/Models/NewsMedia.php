<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsMedia extends Model
{
    protected $table = 'news_media';

    protected $fillable = ['news_id', 'file_path', 'media_type'];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id');
    }
}