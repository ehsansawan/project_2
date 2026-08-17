<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\Donation;
use App\Models\Profile;
use App\Models\ProjectParticipant;
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

        $this->appendStats($profile);

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

        $this->appendStats($profile);

        $message="Profile found";
        $code=200;
        return ['data'=>$profile,'message'=>$message,'code'=>$code];
    }

    /**
     * volunteering_count only counts approved applications (pending/rejected are excluded).
     */
    private function appendStats(Profile $profile): void
    {
        $profile->volunteering_count = ProjectParticipant::query()
            ->where('user_id', $profile->user_id)
            ->where('status', 'approved')
            ->count();

        $donationStats = Donation::query()
            ->where('user_id', $profile->user_id)
            ->selectRaw('COALESCE(SUM(payment), 0) as total_donated, COUNT(*) as donation_count')
            ->first();

        $profile->total_donated = (float) $donationStats->total_donated;
        $profile->donation_count = (int) $donationStats->donation_count;
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
    public function deleteAvatar()
    {
        $user=auth()->user();
        $profile=$user->profile()->with('user')->first();

        if(!$profile){
            $message="Profile not found";
            $code=404;
            return ['data'=>$profile,'message'=>$message,'code'=>$code];
        }

        $image=$profile->getRawOriginal('image');

        if(empty($image)){
            $message="No avatar to delete";
            $code=422;
            return ['data'=>$profile,'message'=>$message,'code'=>$code];
        }

        if(!filter_var($image, FILTER_VALIDATE_URL)){
            $this->destroyPicture($image);
        }

        $profile->update(['image'=>null]);

        $profile=$user->profile()->with('user')->first();

        $message="Avatar deleted successfully";
        $code=200;
        return ['data'=>$profile,'message'=>$message,'code'=>$code];
    }

}
