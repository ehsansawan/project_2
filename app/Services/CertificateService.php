<?php

namespace App\Services;

class CertificateService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function index()
    {
        $user=auth()->user();
        $ceritificate=$user->userCertificates()->with('user.profile')->latest()->get();

        $message='certificates retrieved successfully';
        $code=200;
        return ['data'=>$ceritificate,'message'=>$message,'code'=>$code];

    }
    public function show($id)
    {
        $user=auth()->user();
        $certificate=$user->userCertificates()->with('user.profile')->find($id);
        if(!$certificate)
        {
            $message='certificate not found';
            $code=404;
            return ['data'=>$certificate,'message'=>$message,'code'=>$code];
        }
        $message='certificate retrieved successfully';
        $code=200;
        return ['data'=>$certificate,'message'=>$message,'code'=>$code];
    }
    public function store($request)
    {

    }
    public function update($request, $id)
    {

    }
    public function destroy($id)
    {

    }
}
