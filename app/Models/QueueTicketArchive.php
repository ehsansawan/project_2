<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTicketArchive extends Model
{
     protected $table = 'queue_tickets_archive';

    protected $fillable = [
        'original_ticket_id', 'service_id', 'user_name', 'current_number', 
        'status', 'archival_reason', 'is_manual', 'joined_at', 'called_at', 'served_at', 
        'served_by_employee_id', 'archived_at'
    ];

    protected $casts = [
        'status' => 'string',
        'archival_reason' => 'string',
        'is_manual' => 'boolean',
        'joined_at' => 'datetime',
        'called_at' => 'datetime',
        'served_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by_employee_id');
    }
}
