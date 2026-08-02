<?php

namespace App\Models;

use App\Enums\AuditAction;
use App\Enums\RejectionReason;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'action',
    ];

    protected $casts = [
        'action' => AuditAction::class,
        'reason' => RejectionReason::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
