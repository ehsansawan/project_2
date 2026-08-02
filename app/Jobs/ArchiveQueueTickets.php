<?php

namespace App\Jobs;

use App\Models\QueueTicket;
use App\Models\QueueTicketArchive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveQueueTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Starting queue archival process...');

        $archivedCount = DB::transaction(function () {
            $tickets = QueueTicket::whereIn('status', ['completed', 'no_show', 'waiting', 'serving'])
                ->whereDate('joined_at', '<=', today())
                ->get();

            $count = 0;

            foreach ($tickets as $ticket) {
                // ✅ تسجيل الحالة الأصلية للتشخيص
                Log::info("Processing ticket #{$ticket->id} with status: {$ticket->status}");

                $archivalReason = match ($ticket->status) {
                    'completed' => 'served',
                    'no_show' => 'visitor_absent',
                    'waiting', 'serving' => 'end_of_day',
                    default => 'served',
                };

                // ✅ تسجيل السبب الذي تم تحديده
                Log::info("Ticket #{$ticket->id} -> archival_reason: {$archivalReason}");

                $newStatus = in_array($ticket->status, ['waiting', 'serving']) ? 'no_show' : $ticket->status;

                $archiveData = [
                    'original_ticket_id' => $ticket->id,
                    'service_id' => $ticket->service_id,
                    'user_name' => $ticket->user_name,
                    'current_number' => $ticket->current_number,
                    'status' => $newStatus,
                    'archival_reason' => $archivalReason,
                    'is_manual' => $ticket->is_manual,
                    'joined_at' => $ticket->joined_at,
                    'called_at' => $ticket->called_at,
                    'served_at' => $ticket->served_at,
                    'served_by_employee_id' => $ticket->served_by_employee_id,
                    'archived_at' => now(),
                ];

                // ✅ تسجيل البيانات قبل الحفظ
                Log::info("Archive data for ticket #{$ticket->id}: " . json_encode($archiveData));

                $archived = QueueTicketArchive::create($archiveData);

                // ✅ التحقق من القيمة المحفوظة فعلياً
                Log::info("Archived ticket #{$ticket->id} with archival_reason: {$archived->archival_reason}");

                $ticket->delete();
                $count++;
            }

            return $count;
        });

        Log::info("Queue archival completed. Archived {$archivedCount} tickets.");
    }
}