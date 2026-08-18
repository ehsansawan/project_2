<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\ApplyVolunteerRequest;
use App\Http\Requests\Project\ApproveProjectRequest;
use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\DeleteProjectRequest;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\RejectProjectRequest;
use App\Http\Requests\Project\SubmitProjectRequest;
use App\Http\Requests\Project\UnvoteProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\VoteProjectRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ProjectDonationService;
use App\Services\ProjectService;
use App\Services\ProjectVolunteerService;
use App\Services\ProjectVoteService;
use Illuminate\Http\Request;
use Throwable;

class ProjectController extends Controller
{
    private ProjectService $projectService;
    private ProjectVoteService $projectVoteService;
    private ProjectVolunteerService $projectVolunteerService;
    private ProjectDonationService $projectDonationService;

    public function __construct(
        ProjectService $projectService,
        ProjectVoteService $projectVoteService,
        ProjectVolunteerService $projectVolunteerService,
        ProjectDonationService $projectDonationService
    ) {
        $this->projectService = $projectService;
        $this->projectVoteService = $projectVoteService;
        $this->projectVolunteerService = $projectVolunteerService;
        $this->projectDonationService = $projectDonationService;
    }

    public function store(CreateProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->store($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function index(IndexProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->index($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function show($id)
    {
        $data = [];
        try {
            $data = $this->projectService->show(['id' => $id]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function update(UpdateProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->update($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function destroy(DeleteProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->destroy($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function pendingApproval()
    {
        $data = [];
        try {
            $data = $this->projectService->pendingApproval([]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function submitForReview(SubmitProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->submitForReview($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function approve(ApproveProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->approve($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function reject(RejectProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->reject($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function publicIndex(IndexProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectService->publicIndex($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function votable(Request $request)
    {
        $data = [];
        try {
            $data = $this->projectVoteService->listVotable([]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function vote(VoteProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectVoteService->vote($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function unvote(UnvoteProjectRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectVoteService->unvote($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function votingStats($id)
    {
        $data = [];
        try {
            $data = $this->projectVoteService->projectStats(['id' => $id]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function applyVolunteer(ApplyVolunteerRequest $request)
    {
        $data = [];
        try {
            $data = $this->projectVolunteerService->apply($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function listDonations($id)
    {
        $data = [];
        try {
            $data = $this->projectDonationService->listDonations(['id' => $id]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function donationStats($id)
    {
        $data = [];
        try {
            $data = $this->projectDonationService->stats(['id' => $id]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function topDonors($id, Request $request)
    {
        $data = [];
        try {
            $data = $this->projectDonationService->topDonors(['id' => $id, 'limit' => $request->query('limit')]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
}