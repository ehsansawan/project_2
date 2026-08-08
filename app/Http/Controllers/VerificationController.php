<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerificationRequest\AdminIndexRequest;
use App\Http\Requests\VerificationRequest\RejectVerificationRequest;
use App\Http\Requests\VerificationRequest\SearchVerificationRequest;
use App\Http\Requests\VerificationRequest\StoreVerificationFormRequest;
use App\Http\Requests\VerificationRequest\UpdateVerificationFormRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminVerificationService;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Throwable;

class VerificationController extends Controller
{
    //
    private VerificationService $verificationService;
    private AdminVerificationService $adminVerificationService;

    public function __construct(VerificationService $verificationService,AdminVerificationService $adminVerificationService)
    {
        $this->verificationService = $verificationService;
        $this->adminVerificationService = $adminVerificationService;
    }

    public function index()
    {
        $data=[];
        try{

            $data=$this->verificationService->index();
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

            $data=$this->verificationService->show($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function store(StoreVerificationFormRequest $request)
    {
        $data=[];
        try{

            $data=$this->verificationService->store_verification($request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function update(UpdateVerificationFormRequest $request)
    {

        $data=[];
        try{

            $data=$this->verificationService->update_verification($request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function searchByNationalId(SearchVerificationRequest $request)
    {
        $data=[];
        $data=[];
        try{

            $data=$this->verificationService->SearchByNationalId($request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }

    }

//

    //for admins
    public function adminIndex(AdminIndexRequest $request)
    {
        $data=[];
        try{

            $data=$this->adminVerificationService->adminIndex($request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function adminShow($id)
    {
        $data=[];
        try{

            $data=$this->adminVerificationService->adminShow($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function adminIndexByUser($user_id)
    {
        $data=[];
        try{

            $data=$this->adminVerificationService->adminIndexByUser($user_id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function approve($id)
    {
        $data=[];
        try{

            $data=$this->adminVerificationService->Approve($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function reject($id,RejectVerificationRequest $request)
    {
        $data=[];
        try{

            $data=$this->adminVerificationService->Reject($id,$request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }

}
