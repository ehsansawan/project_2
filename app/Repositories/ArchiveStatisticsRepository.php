<?php

namespace App\Repositories;

use App\Models\QueueTicketArchive;
use Illuminate\Support\Facades\DB;

class ArchiveStatisticsRepository
{
    public function getOverview(string $period = 'today'): array
    {
        $query = QueueTicketArchive::query();

        if ($period === 'today') {
            $query->whereDate('archived_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('archived_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereBetween('archived_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $totalServed = (clone $query)->where('archival_reason', 'served')->count();
        $totalNoShow = (clone $query)->where('archival_reason', 'visitor_absent')->count();
        $totalEndOfDay = (clone $query)->where('archival_reason', 'end_of_day')->count();

        $avgWaitTime = (clone $query)
            ->whereNotNull('called_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, joined_at, called_at)) as avg_minutes')
            ->value('avg_minutes') ?? 0;

        $busiestHour = (clone $query)
            ->selectRaw('HOUR(joined_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(1)
            ->value('hour');

        return [
            'total_served' => $totalServed,
            'total_no_show' => $totalNoShow,
            'total_end_of_day' => $totalEndOfDay,
            'avg_wait_time_minutes' => round($avgWaitTime, 1),
            'busiest_hour' => $busiestHour ? sprintf('%02d:00', $busiestHour) : null,
        ];
    }

    public function getStatsByService(string $period = 'today'): array
    {
        $query = QueueTicketArchive::with('service');

        if ($period === 'today') {
            $query->whereDate('archived_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('archived_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereBetween('archived_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        return $query->get()
            ->groupBy('service_id')
            ->map(function ($tickets) {
                $service = $tickets->first()->service;
                return [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'served' => $tickets->where('archival_reason', 'served')->count(),
                    'no_show' => $tickets->where('archival_reason', 'visitor_absent')->count(),
                    'end_of_day' => $tickets->where('archival_reason', 'end_of_day')->count(),
                    'avg_wait_minutes' => round($tickets->filter(fn($t) => $t->called_at)
                        ->avg(fn($t) => $t->joined_at->diffInMinutes($t->called_at)), 1),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getEmployeeStats(string $period = 'today'): array
    {
        $query = QueueTicketArchive::with('servedBy')
            ->whereNotNull('served_by_employee_id');

        if ($period === 'today') {
            $query->whereDate('archived_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('archived_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereBetween('archived_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        return $query->get()
            ->groupBy('served_by_employee_id')
            ->map(function ($tickets) {
                $employee = $tickets->first()->servedBy;
                $serviceTimes = $tickets->filter(fn($t) => $t->served_at && $t->called_at)
                    ->map(fn($t) => $t->called_at->diffInMinutes($t->served_at));

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                    'tickets_served' => $tickets->count(),
                    'avg_service_time_minutes' => round($serviceTimes->avg(), 1),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getArchiveHistory(array $filters, int $perPage = 20)
    {
        $query = QueueTicketArchive::with('servedBy') // ❌ حذف 'service' من هنا
        ->select([
            'id', 'original_ticket_id', 'service_id', 'user_name', 'current_number',
            'status', 'archival_reason', 'is_manual', 'joined_at', 'called_at',
            'served_at', 'served_by_employee_id', 'archived_at'
        ]);

        if (isset($filters['date'])) {
            $query->whereDate('archived_at', $filters['date']);
        }

        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['archival_reason'])) {
            $query->where('archival_reason', $filters['archival_reason']);
        }

        $paginator = $query->orderByDesc('archived_at')->paginate($perPage);

        // ✅ تخصيص البيانات المرجعة
        $items = collect($paginator->items())->map(function ($ticket) {
            // إضافة display_number
            $service = \App\Models\Service::find($ticket->service_id);
            $prefix = $service ? strtoupper($service->prefix) : 'A';
            $ticket->display_number = $prefix . str_pad($ticket->current_number, 3, '0', STR_PAD_LEFT);
            
            // إخفاء العلاقات غير المرغوبة
            $ticket->makeHidden(['service']);
            
            return $ticket;
        });

        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'data' => $items,
        ];
    }
}