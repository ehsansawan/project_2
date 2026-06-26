<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplainCategory extends Model
{
    use SoftDeletes;
    protected $fillable = ['name'];

    public function complain()
    {
        return $this->hasMany(Complain::class, 'category_id');
    }
}
