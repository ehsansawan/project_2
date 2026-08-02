<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\Profile;
use App\Traits\PictureTrait;

class ProfileService
{
    /**
     * Create a new class instance.
     */
    use PictureTrait;
    public function __construct()
    {
        //
    }

    public function index()
    {
        $user=auth()->user();

        $profile=$user->profile()->with('user')->first();

        if(!$profile){
            $message="Profile not found";
            $code=404;
            return ['data'=>$profile,'message'=>$message,'code'=>$code];
        }

        $message="Profile retrieved successfully";
        $code=200;
        return ['data'=>$profile,'message'=>$message,'code'=>$code];

    }
    public function show($id)
    {
        $profile=Profile::query()->with('user')->find($id);

        if(!$profile){
            $message="Profile not found";
            $code=404;
            return ['data'=>$profile,'message'=>$message,'code'=>$code];
        }

        $message="Profile found";
        $code=200;
        return ['data'=>$profile,'message'=>$message,'code'=>$code];
    }
    public function update($request)
    {

       $user=auth()->user();

       if ($user->account_status == AccountStatus::Visitor->value) {
           $user->update([
               'first_name' => $request['first_name'] ?? $user->first_name,
               'last_name' => $request['last_name'] ?? $user->last_name,
               'birth_date' => $request['birth_date'] ?? $user->birth_date,
           ]);
       }

       $image=$request['image']??null;

       if($image)
       {
           $path = $this->updatePicture(
               $image,
               $user->profile->getRawOriginal('image'),
               'uploads/profiles'
           );
       }

        $user->profile->update([
                'image'=>$path??$user->profile->image,

            ]
        );


       $profile=$user->profile()->with('user')->first();


       $message="Profile updated successfully";
       $code=200;
       return ['data'=>$profile,'message'=>$message,'code'=>$code];
    }

}
