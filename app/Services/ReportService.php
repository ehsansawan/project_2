<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private ReportRepository $reportRepository;

    public function __construct(ReportRepository $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    public function createReport(array $validatedData, int $userId): array
    {
       $complain = $this->reportRepository->getComplainById($validatedData['complain_id']);

        if(!$complain)
            return [
                'data' => null,
                'message' => 'Complain not found',
                'code' => 404
            ];
            
        if($complain->status !== 'published')
            return [
                'data' => null,
                'message' => 'You can only report published complaints',
                'code' => 400
            ];

        if($this->reportRepository->checkIfAlreadyReported($userId, $validatedData['complain_id']))
            return [
                'data' => null,
                'message' => 'You have already reported this complain',
                'code' => 400
            ];

        $report = DB::transaction(function () use ($validatedData, $userId) {
            return $this->reportRepository->createReport([
                'user_id' => $userId,
                'complain_id' => $validatedData['complain_id'],
                'type_id' => $validatedData['type_id'],
                'description' => $validatedData['description'],
                'status' => 'pending',
                'reported_at' => now()->toDateString(),
            ]);
        });


        return [
            'data' => $report,
            'message' => 'Report created successfully',
            'code' => 201
        ];
    }
    
}
