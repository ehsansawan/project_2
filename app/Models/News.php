<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    use SoftDeletes;

    protected $table = 'news';

    protected $fillable = [
        'title', 'description', 'type', 'target_audience', 'created_by',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'published_at', 'pin_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function pin(): BelongsTo
    {
        return $this->belongsTo(MapPin::class, 'pin_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(NewsMedia::class, 'news_id');
    }
}