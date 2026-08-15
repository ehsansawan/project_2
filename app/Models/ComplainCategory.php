<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplainCategory extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'weight'];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function complains()
    {
        return $this->hasMany(Complain::class, 'category_id');
    }
}
