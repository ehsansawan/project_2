<?php

namespace App\Repositories;

use App\Models\Complain;
use App\Models\Report;

class ReportRepository
{
    public function getComplainById(int $complianId) : ?complain
    {
        return Complain::query()->find($complianId);
    }

    public function checkIfAlreadyReported(int $userId, int $complainId) : bool
    {
        return Report::query()
            ->where('complain_id', $complainId)
            ->where('user_id', $userId)
            ->exists();
    }
    
    public function createReport(array $data) : Report
    {
        return Report::query()->create($data);
    }
}