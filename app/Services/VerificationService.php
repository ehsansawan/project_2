<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\VerificationStatus;
use App\Models\VerificationImages;
use App\Models\VerificationRequests;
use App\Traits\PictureTrait;
use Illuminate\Support\Facades\DB;

class VerificationService
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
        $user = auth()->user();

        $verificationRequests = $user->verificationRequests()
            ->with('user.profile','images','rejections')
            ->latest()
            ->get();

        return [
            'data' => $verificationRequests,
            'message' => 'Verification requests retrieved successfully.',
            'code' => 200,
        ];
    }
    public function show($id)
    {

        $user = auth()->user();

        $verificationRequest = $user->verificationRequests()
            ->with('images')
            ->find($id);

        if(!$verificationRequest)
        {
            $message = 'Verification request not found.';
            $code = 404;
            return ['data' => $verificationRequest, 'message' => $message, 'code' => $code];
        }

        return [
            'data' => $verificationRequest,
            'message' => 'Verification request retrieved successfully.',
            'code' => 200,
        ];

    }
    public function store_verification($request)
    {

        $user=auth()->user();

        if($user->account_status==AccountStatus::Closed->value)
            {
                $message="Your account has been closed";
                $code=403;
              return ['data'=>$user,'message'=>$message,'code'=>$code];
            }

         if($user->account_status==AccountStatus::Verified->value)
           {
               $message='your Account already verified';
               $code=409;
              return ['data'=>$user,'message'=>$message,'code'=>$code];
           }

        if ($user->verification_attempts>=3)
           {
               $message='you reached the limit of verification attempts';
               $code=403;
               return ['data'=>$user,'message'=>$message,'code'=>$code];
           }



        $hasPending = $user->verificationRequests()
            ->where('status', VerificationStatus::Pending->value)
            ->exists();

        if ($hasPending) {
            $message="You are under pending now,please wait for reviewing";
            $code=401;
            return ['data'=>$user,'message'=>$message,'code'=>$code];
        }

        DB::beginTransaction();

        try
        {
            $verification_request=VerificationRequests::query()->create(
                [
                    'user_id'=>$user->id,
                    'national_id'=>$request['national_id'],
                    'status'=>VerificationStatus::Pending->value,
                ]
            );

            $images=$request['images'];


            foreach ($images as $image)
            {
                $file_url=$this->StorePicture($image,'uploads/personalPhotos');
                VerificationImages::query()->create(
                    [
                      'verification_request_id'=>$verification_request->id,
                       'image_url'=>$file_url,
                    ]
                );


            }

            $user->update([
                'verification_attempts' => $user->verification_attempts + 1,
            ]);


            $verification_request->load('images');




            DB::commit();

        }
        catch (\Exception $e)
        {
           DB::rollBack();
           $message=$e->getMessage();

           return ['data'=>$user,'message'=>$message,'code'=>500];

        }

          $message="your request has been submitted";
          $code=200;

        return ['data'=>$verification_request,'message'=>$message,'code'=>$code];




    }
    public function update_verification($request)
    {


        $user = auth()->user();


        // Account closed
        if ($user->account_status == AccountStatus::Closed->value) {
            return [
                'data' => null,
                'message' => 'Your account has been closed.',
                'code' => 403,
            ];
        }

        // Already verified
        if ($user->account_status == AccountStatus::Verified->value) {
            return [
                'data' => null,
                'message' => 'Your account is already verified.',
                'code' => 409,
            ];
        }


         $verification=$user->verificationRequests()->findOrFail($request['id']);



        if($verification->status!=VerificationStatus::Pending->value )
        {
            $message="you can update pending requests only";
            $code=403;

            return ['data'=>$user,'message'=>$message,'code'=>$code];
        }


        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Pending
            |--------------------------------------------------------------------------
            */

            if ($verification->status == VerificationStatus::Pending->value) {

                $verification->update([
                    'national_id' => $request['national_id']??$verification->national_id,
                ]);

                $images_to_delete=$request['images_to_delete']??[];

                // u have to ensure that the id of image you want to delete is the same with the id of verification->imgae->id

                foreach ($images_to_delete as $image_id)
                {
                    $image = $verification->images()
                        ->findOrFail($image_id);
                     $this->DestroyPicture( $image->getRawOriginal('image_url'));
                     $image->delete();
                }

                $images_to_upload=$request['images_to_upload']??[];

                foreach ($images_to_upload as $image) {

                    $path = $this->StorePicture(
                        $image,
                        'uploads/personalPhotos'
                    );

                    VerificationImages::create([
                        'verification_request_id' => $verification->id,
                        'image_url' => $path,
                    ]);
                }

                $verification->load(['user.profile','images','rejections']);
            }


            DB::commit();

            return [
                'data' => $verification,
                'message' => 'Verification request updated successfully.',
                'code' => 200,
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'data' => null,
                'message' => $e->getMessage(),
                'code' => 500,
            ];
        }
    }
    public function searchByNationalId($request)
    {
        $user = auth()->user();

        $nationalId = trim($request['national_id'] ?? '');

        if ($nationalId === '') {
            return [
                'data' => null,
                'message' => 'national_id is required.',
                'code' => 422,
            ];
        }

        $verificationRequests = $user->verificationRequests()
            ->with('images')
            ->where('national_id', $nationalId)
            ->latest()
            ->get();

        if ($verificationRequests->isEmpty()) {
            return [
                'data' => $verificationRequests,
                'message' => 'No verification request found with this national id.',
                'code' => 404,
            ];
        }

        return [
            'data' => $verificationRequests,
            'message' => 'Verification requests retrieved successfully.',
            'code' => 200,
        ];
    }

}
