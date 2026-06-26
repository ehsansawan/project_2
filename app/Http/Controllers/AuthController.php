<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    //
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService=$authService;
    }


    public function register(RegisterRequest $request): JsonResponse
    {
        $data=[];
        try{

            $data=$this->authService->register($request->validated());
            return  ApiResponse::success($data['user'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function login(LoginRequest $request): JsonResponse
    {
        $data=[];
        try{
            $data=$this->authService->login($request->validated());
            return  ApiResponse::success($data['user'],$data['message'],$data['code']);
        }
        catch (Throwable $th){
            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function logout(): JsonResponse
    {
        $data=[];
        try {
            $data=$this->authService->logout();
            return  ApiResponse::success($data['user'],$data['message'],$data['code']);
        }
        catch (Throwable $th){
            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function refresh(Request $request):JsonResponse
    {

        $data=[];
        try {
            $data=$this->authService->refresh($request);
            return ApiResponse::success($data['user'],$data['message'],$data['code']);
        }
        catch (Throwable $th){
            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }

    }
    public function forgetPassword(Request $request):JsonResponse
    {
        $data=[];

        try {
            $data=$this->authService->forgetPassword($request);
            return ApiResponse::success($data['info'],$data['message'],$data['code']);
        }
        catch (Throwable $th){
            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }

    }
    public function checkCode(Request $request):JsonResponse
    {
        $data=[];

        try {
            $data=$this->authService->checkCode($request);
            return ApiResponse::success($data['info'],$data['message'],$data['code']);
        }
        catch (Throwable $th){
            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function resetPassword(ResetPasswordRequest $request):JsonResponse
    {
        $data=[];

        try {
            $data=$this->authService->resetPassword($request->validated());
            return ApiResponse::success($data['info'],$data['message'],$data['code']);
        }
        catch (Throwable $th){
            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }


}
