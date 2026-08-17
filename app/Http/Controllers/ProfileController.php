<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ProfileService;
use Throwable;

class ProfileController extends Controller
{
    //
    private ProfileService $profileService;
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }
    public function index()
    {
        $data=[];
        try{

            $data=$this->profileService->index();
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function show($id)
    {
        $data=[];
        try{

            $data=$this->profileService->show($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }

    public function update(UpdateProfileRequest $request)
    {

        $data=[];
        try{

            $data=$this->profileService->update($request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }

    public function destroyAvatar()
    {

        $data=[];
        try{

            $data=$this->profileService->deleteAvatar();
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
}
