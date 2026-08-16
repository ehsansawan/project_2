<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\ApproveProjectRequest;
use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\DeleteProjectRequest;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\RejectProjectRequest;
use App\Http\Requests\Project\SubmitProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ProjectService;
use Throwable;

class ProjectController extends Controller
{
    private ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
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
}