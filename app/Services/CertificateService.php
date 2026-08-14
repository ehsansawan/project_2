<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Models\UserCertificate;
use App\Traits\PictureTrait;

class CertificateService
{
    use PictureTrait;
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
        $ceritificate=$user->userCertificates()->with('userSkill.user')->latest()->get();

        $message='certificates retrieved successfully';
        $code=200;
        return ['data'=>$ceritificate,'message'=>$message,'code'=>$code];

    }
    public function show($id)
    {
        $user=auth()->user();
        $certificate=$user->userCertificates()->with('userSkill.user')->find($id);
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
         $user=auth()->user();
         $user_skill_id=$request['user_skill_id'];
         $file=$request['file_path'];

        $skill = $user->userSkills()->where('id', $user_skill_id)->first();

        if (!$skill)
        {
            $message='skill not found';
            $code=404;
            return ['data'=>$skill,'message'=>$message,'code'=>$code];
        }

         $file_url=$this->StorePicture($file,'uploads/certificates');

         $certificate=UserCertificate::query()->create([
             'user_skill_id'=>$user_skill_id,
             'file_path'=>$file_url,
             'status'=>CertificateStatus::Pending->value
         ]);


         $message='certificate created successfully';
         $code=200;
         return ['data'=>$certificate,'message'=>$message,'code'=>$code];

    }
    public function update($request, $id)
    {
      $user=auth()->user();
      $certificate=$user->userCertificates()->find($id);

      if(!$certificate)
      {
          $message='certificate not found';
          $code=404;
          return ['data'=>$certificate,'message'=>$message,'code'=>$code];
      }

      if($certificate->status!=CertificateStatus::Pending->value)
      {
          $message='you can update pending certificates only';
          $code=403;
          return ['data'=>$certificate,'message'=>$message,'code'=>$code];
      }

        if ($request['user_skill_id'] ?? null) {
            $newSkill = $user->userSkills()->find($request['user_skill_id']);
            if (!$newSkill)
            {
                $message='skill not found';
                $code=404;
                return ['data'=>$newSkill,'message'=>$message,'code'=>$code];
            }
        }

      $file=$request['file_path']??null;

        if ($file) {
            $this->DestroyPicture($certificate->getRawOriginal('file_path'));
            $certificate->file_path = $this->StorePicture($file, 'uploads/certificates');
        }


      $certificate->user_skill_id=$request['user_skill_id']??$certificate->user_skill_id;
      $certificate->save();



      $message='certificate updated successfully';
      $code=200;
      return ['data'=>$certificate,'message'=>$message,'code'=>$code];

    }
    public function destroy($id)
    {
       $user=auth()->user();
       $certificate=$user->userCertificates()->find($id);

       if(!$certificate)
       {
           $message='certificate not found';
           $code=404;
           return ['data'=>$certificate,'message'=>$message,'code'=>$code];
       }


        $file=$certificate->file_path;
        if ($file) {
            $this->DestroyPicture($certificate->getRawOriginal('file_path'));
        }

        $certificate->delete();

       $message='certificate deleted successfully';
       $code=200;
       return ['data'=>$certificate,'message'=>$message,'code'=>$code];
    }
}
