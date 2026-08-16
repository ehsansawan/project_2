<?php

namespace App\Http\Controllers;

use App\Http\Requests\Certifications\getCertificateRequest;
use App\Http\Requests\Certifications\RejectCertificateRequest;
use App\Http\Requests\Certifications\StoreCertificateRequest;
use App\Http\Requests\Certifications\UpdateCertificateRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminCertificateService;
use App\Services\CertificateService;
use Throwable;

class UserCertificateController extends Controller
{
    //
    private CertificateService $certificateService;
    private AdminCertificateService $adminCertificateService;

    public function __construct(CertificateService $certificateService,
                                AdminCertificateService $adminCertificateService)
    {
        $this->certificateService = $certificateService;
        $this->adminCertificateService = $adminCertificateService;
    }

    // for users
    public function index()
    {
        $data=[];
        try{

            $data=$this->certificateService->index();
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

            $data=$this->certificateService->show($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function store(StoreCertificateRequest $request)
    {
        $data=[];
        try{

            $data=$this->certificateService->store($request->all());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function update(UpdateCertificateRequest $request, $id)
    {
        $data=[];
        try{

            $data=$this->certificateService->update($request->all(),$id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function destroy($id)
    {
        $data=[];
        try{

            $data=$this->certificateService->destroy($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }

    // for admins
    public function getCertificates(getCertificateRequest $request)
    {

        $data=[];
        try{

            $data=$this->adminCertificateService->getCertificates($request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function getCertificate($id)
    {
        $data=[];
        try{

            $data=$this->adminCertificateService->getCertificate($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function getUserCertificates($user_id)
    {
        $data=[];
        try{

            $data=$this->adminCertificateService->getUserCertificates($user_id);
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

            $data=$this->adminCertificateService->approve($id);
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }
    public function reject($id, RejectCertificateRequest $request)
    {
        $data=[];
        try{

            $data=$this->adminCertificateService->reject($id,$request->validated());
            return  ApiResponse::success($data['data'],$data['message'],$data['code']);
        }
        catch (Throwable $th){

            $message=$th->getMessage();
            return ApiResponse::error($data,$message);
        }
    }

}
