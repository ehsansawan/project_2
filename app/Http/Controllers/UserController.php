<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\CreateAdminRequest;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Services\UserService;
use Throwable;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(IndexUserRequest $request)
    {
        $data = [];
        try {
            $data = $this->userService->index($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function create(CreateUserRequest $request)
    {
        $data = [];
        try {
            $data = $this->userService->create($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
    public function createAdmin(CreateAdminRequest $request)
    {
        $data = [];
        try {
            $data = $this->userService->createAdmin($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function update(UpdateUserRequest $request)
    {
        $data = [];
        try {
            $data = $this->userService->update($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
    public function destroy(DeleteUserRequest $request)
    {
        $data = [];
        try {
            $data = $this->userService->destroy($request->validated());
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
}
