<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\ApproveVolunteerApplicationRequest;
use App\Http\Requests\Project\ForceCloseVotingRequest;
use App\Http\Requests\Project\RecordDonationRequest;
use App\Http\Requests\Project\RejectVolunteerApplicationRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminProjectService;
use App\Services\ProjectDonationService;
use Illuminate\Http\Request;
use Throwable;

class AdminProjectController extends Controller
{
    private AdminProjectService $adminProjectService;
    private ProjectDonationService $projectDonationService;

    public function __construct(AdminProjectService $adminProjectService, ProjectDonationService $projectDonationService)
    {
        $this->adminProjectService = $adminProjectService;
        $this->projectDonationService = $projectDonationService;
    }

    public function votingOverview()
    {
        $data = [];
        try {
            $data = $this->adminProjectService->votingOverview();
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function votingStatistics($id)
    {
        $data = [];
        try {
            $data = $this->adminProjectService->votingStatistics(['id' => $id]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function listVolunteerApplications($id, Request $request)
    {
        $data = [];
        try {
            $data = $this->adminProjectService->listVolunteerApplications(['id' => $id, 'status' => $request->query('status')]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function approveVolunteerApplication(ApproveVolunteerApplicationRequest $request)
    {
        $data = [];
        try {
            $data = $this->adminProjectService->approveVolunteerApplication($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function rejectVolunteerApplication(RejectVolunteerApplicationRequest $request)
    {
        $data = [];
        try {
            $data = $this->adminProjectService->rejectVolunteerApplication($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function forceCloseVoting(ForceCloseVotingRequest $request)
    {
        $data = [];
        try {
            $data = $this->adminProjectService->forceCloseVoting($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function recordDonation(RecordDonationRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectDonationService->record($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
}
