<?php

namespace App\Services;

use App\Repositories\QueueRepository;
use App\Models\QueueTicket;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Support\Facades\DB;

class AdminQueueService
{
    private QueueRepository $queueRepository;

    public function __construct(QueueRepository $queueRepository)
    {
        $this->queueRepository = $queueRepository;
    }

    private function checkEmployeeAssignment(int $employeeId, int $serviceId): void
    {
        if (!$this->queueRepository->isEmployeeAssigned($employeeId, $serviceId)) {
            throw new AccessDeniedHttpException('You are not assigned to manage this service queue.');
        }
    }

    public function getDashboard(int $serviceId, int $employeeId): array
    {
        $this->checkEmployeeAssignment($employeeId, $serviceId);

        $service = $this->queueRepository->getServiceById($serviceId);
        if (!$service) throw new NotFoundHttpException('Service not found');

        $currentlyServing = $this->queueRepository->getCurrentlyServing($serviceId);
        $waitingList = $this->queueRepository->getWaitingList($serviceId);
        $noShowList = $this->queueRepository->getNoShowList($serviceId);

        return [
            'data' => [
                'service' => $service,
                'currently_serving' => $currentlyServing,
                'waiting_list' => $waitingList,
                'no_show_list' => $noShowList,
                'stats' => [
                    'waiting_count' => $waitingList->count(),
                    'served_today' => $this->queueRepository->getCompletedTodayCount($serviceId),
                ]
            ],
            'message' => 'Queue dashboard retrieved successfully',
            'code' => 200,
        ];
    }

    public function addManualVisitor(int $serviceId, int $employeeId, array $validatedData): array
    {
        $this->checkEmployeeAssignment($employeeId, $serviceId);

        return DB::transaction(function () use ($serviceId, $validatedData) {
            $lastNumber = $this->queueRepository->getLastTicketNumberToday($serviceId);
            $newNumber = $lastNumber + 1;

            $ticket = $this->queueRepository->createTicket([
                'service_id' => $serviceId,
                'user_name' => $validatedData['user_name'],
                'current_number' => $newNumber,
                'status' => 'waiting',
                'is_manual' => true,
                'joined_at' => now(),
            ]);

            return [
                'data' => [
                    'ticket' => $ticket,
                    'display_number' => $ticket->display_number,
                ],
                'message' => 'Visitor added to queue manually',
                'code' => 201,
            ];
        });
    }

    public function callNext(int $serviceId, int $employeeId): array
    {
        $this->checkEmployeeAssignment($employeeId, $serviceId);

        $currentlyServing = $this->queueRepository->getCurrentlyServing($serviceId);
        if ($currentlyServing) {
            throw new ConflictHttpException('There is already a visitor being served. Please mark them as served or no-show first.');
        }

        $nextTicket = QueueTicket::where('service_id', $serviceId)
            ->where('status', 'waiting')
            ->orderBy('current_number', 'asc')
            ->first();

        if (!$nextTicket) {
            throw new NotFoundHttpException('No visitors waiting in the queue.');
        }

        $nextTicket->update([
            'status' => 'serving',
            'called_at' => now(),
        ]);

        return [
            'data' => $nextTicket,
            'message' => 'Next visitor called successfully',
            'code' => 200,
        ];
    }

    public function markAsServed(int $ticketId, int $employeeId): array
    {
        $ticket = QueueTicket::find($ticketId);
        if (!$ticket) throw new NotFoundHttpException('Ticket not found');

        $this->checkEmployeeAssignment($employeeId, $ticket->service_id);

        if ($ticket->status !== 'serving') {
            throw new BadRequestHttpException('Only the currently serving visitor can be marked as served.');
        }

        $ticket->update([
            'status' => 'completed',
            'served_at' => now(),
            'served_by_employee_id' => $employeeId,
        ]);

        return [
            'data' => $ticket,
            'message' => 'Visitor marked as served successfully',
            'code' => 200,
        ];
    }

    public function markAsNoShow(int $ticketId, int $employeeId): array
    {
        $ticket = QueueTicket::find($ticketId);
        if (!$ticket) throw new NotFoundHttpException('Ticket not found');

        $this->checkEmployeeAssignment($employeeId, $ticket->service_id);

        if ($ticket->status !== 'serving') {
            throw new BadRequestHttpException('Only the currently serving visitor can be marked as no-show.');
        }

        $ticket->update([
            'status' => 'no_show',
        ]);

        return [
            'data' => $ticket,
            'message' => 'Visitor marked as no-show',
            'code' => 200,
        ];
    }

    public function returnToQueue(int $ticketId, int $employeeId): array
    {
        $ticket = QueueTicket::find($ticketId);
    if (!$ticket) throw new NotFoundHttpException('Ticket not found');

    $this->checkEmployeeAssignment($employeeId, $ticket->service_id);

    if ($ticket->status !== 'no_show') {
        throw new BadRequestHttpException('Only no-show visitors can be returned to the queue.');
    }

    // حساب الدقائق منذ أن أصبح no_show
    $minutesSinceNoShow = now()->diffInMinutes($ticket->updated_at);
    
    return DB::transaction(function () use ($ticket, $minutesSinceNoShow) {
        if ($minutesSinceNoShow < 15) {
            // 🎯 نحاول وضعه في المركز الثاني
            $waitingTickets = QueueTicket::where('service_id', $ticket->service_id)
                ->where('status', 'waiting')
                ->orderBy('current_number', 'asc')
                ->get();
            
            if ($waitingTickets->count() <= 1) {
                // الطابور فارغ أو شخص واحد فقط ➔ نعطيه الرقم التالي (سيكون الثاني أو الأول)
                $lastNumber = $this->queueRepository->getLastTicketNumberToday($ticket->service_id);
                $newNumber = $lastNumber + 1;
            } else {
                // أكثر من شخص ➔ نزيح الأرقام من الثاني فصاعداً ونضع الغائب في المركز الثاني
                $firstTicket = $waitingTickets->first();
                $newNumber = $firstTicket->current_number + 1;
                
                // نزيح جميع الأشخاص الذين أرقامهم >= newNumber بزيادة 1
                QueueTicket::where('service_id', $ticket->service_id)
                    ->where('status', 'waiting')
                    ->where('current_number', '>=', $newNumber)
                    ->increment('current_number');
            }
            
            $message = 'Visitor returned to second position in queue';
        } else {
            // ⏰ أكثر من 15 دقيقة ➔ نضعه في نهاية الطابور
            $lastNumber = $this->queueRepository->getLastTicketNumberToday($ticket->service_id);
            $newNumber = $lastNumber + 1;
            $message = 'Visitor returned to the end of the queue';
        }

        $ticket->update([
            'status' => 'waiting',
            'current_number' => $newNumber,
            'called_at' => null,
        ]);

        $ticket->append('display_number'); // إضافة الـ display_number للـ response

        return [
            'data' => [
                'ticket' => $ticket,
                'minutes_since_no_show' => $minutesSinceNoShow,
                'new_position' => $newNumber,
            ],
            'message' => $message,
            'code' => 200,
        ];
    });
    }

    public function getEmployeeServices(int $employeeId): array
    {
        return [
            'data' => $this->queueRepository->getAssignedServices($employeeId),
            'message' => 'Assigned services retrieved successfully',
            'code' => 200,
        ];
    }
}