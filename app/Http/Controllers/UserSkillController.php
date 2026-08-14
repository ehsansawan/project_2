<?php

namespace App\Http\Controllers;

use App\Http\Requests\Skill\CreateSkillRequest;
use App\Http\Requests\Skill\UpdateSkillRequest;
use App\Http\Responses\ApiResponse;
use App\Services\SkillService;
use Throwable;

class UserSkillController extends Controller
{
    //
    private SkillService $skillService;
    public function __construct(SkillService $skillService)
    {
        $this->skillService = $skillService;
    }

    public function index()
    {
        $data = [];
        try {
            $data = $this->skillService->index();
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
            $data = $this->skillService->show($id);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
    public function store(CreateSkillRequest $request)
    {
        $data = [];
        try {
            $data = $this->skillService->store($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
    public function update(UpdateSkillRequest $request, $id)
    {
        $data = [];
        try {
            $data = $this->skillService->update($request->validated(), $id);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
    public function destroy($id)
    {
        $data = [];
        try {
            $data = $this->skillService->destroy($id);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
}