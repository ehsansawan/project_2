<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationRejections;
use App\Models\VerificationRequests;
use Illuminate\Support\Facades\DB;

class AdminVerificationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //

    }
    public function adminIndex( array $request): array
    {

        $query = VerificationRequests::query()
            ->with(['user', 'images']);

        if (!empty($request['status'])) {
            $query->whereIn('status', $request['status']);
        }

        $verificationRequests = $query
            ->latest()
            ->paginate(15);

        return [
            'data' => $verificationRequests,
            'message' => 'Verification requests retrieved successfully.',
            'code' => 200,
        ];
    }
    public function adminIndexByUser($user_id)
    {
        $user=User::query()->find($user_id);

        if(!$user)
        {
            $message="User not found";
            $code=404;
            return ['data'=>$user,'message'=>$message,'code'=>$code];
        }

        $verificationRequests = $user->verificationRequests()
            ->with(['user', 'images'])
            ->latest()
            ->paginate(15);

        $message='Verification requests retrieved successfully.';
        $code=200;

        return ['data'=>$verificationRequests,'message'=>$message,'code'=>$code];
    }
    public function adminShow($id)
    {
        $verificationRequest = VerificationRequests::query()
            ->with(['user', 'images'])
            ->find($id);

        if(!$verificationRequest)
        {
            $message="verification not found";
            $code=404;
            return ['data'=>$verificationRequest,'message'=>$message,'code'=>$code];
        }


        return [
            'data' => $verificationRequest,
            'message' => 'Verification request retrieved successfully.',
            'code' => 200,
        ];
    }
    public function Approve(int $id)
    {

        $verification = VerificationRequests::query()
            ->with('user')
            ->find($id);

        if(!$verification){
            $message = 'Verification request not found.';
            $code = 404;

            return ['data'=>null,
                'message' => $message,
                'code' => $code,];
        }

        if ($verification->status != VerificationStatus::Pending->value) {

            return [
                'data' => null,
                'message' => 'Only pending requests can be approved.',
                'code' => 409,
            ];
        }

        DB::beginTransaction();

        try {


            $verification->update([
                'status' => VerificationStatus::Approved->value,
            ]);

            $verification->user->update([
                'account_status' => AccountStatus::Verified->value,
                'credibility_score' => 100,
                'expires_at' => null,
            ]);
            $verification->user->profile->update([
                'credibility_score' => 50,
            ]);

            // Audit Log

            $verification->audits()->create([
                'user_id' => auth()->id(),
                'action' => AuditAction::Approve->value,
            ]);

            // Notification

            DB::commit();

            $verification->load(['user', 'images']);

            return [
                'data' => $verification,
                'message' => 'Verification approved successfully.',
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
    public function Reject(int $id, $request)
    {
        $verification = VerificationRequests::query()
            ->with('user')
            ->find($id);

        if(!$verification){
            $message = 'Verification request not found.';
            $code = 404;

            return ['data'=>null,
                'message' => $message,
                'code' => $code,];
        }


        if ($verification->status != VerificationStatus::Pending->value) {

            return [
                'data' => null,
                'message' => 'Only pending requests can be rejected.',
                'code' => 409,
            ];
        }


        DB::beginTransaction();

        try {


//            $verification->update([
//                'status' => VerificationStatus::Rejected->value,
//            ]);



            VerificationRejections::query()->create(
                [
                    'user_id'=>auth('api')->id() ,// this id for admin who take the action
                    'verification_request_id' => $id,
                    'reason' => $request['reason'],
                    'description' => $request['description']??'',
                ]
            );



            $user = $verification->user;

            if($user->expires_at == null)
            {
                $user->update([
                    'expires_at' => now()->addDays(14),
                ]);
            }
            $verification->status=VerificationStatus::Rejected->value;
            $verification->save();

//            $user->decrement('verification_attempts');
            $user->refresh();

            if ($user->verification_attempts >= 3) {

                $user->update([
                    'account_status' => AccountStatus::Closed->value,
                ]);
            }


            // Audit Log

            $verification->audits()->create([
                'user_id' => auth()->id(),
                'action' => AuditAction::Reject->value,
            ]);

            // Notification

            DB::commit();

            $verification->load(['user', 'images']);

            return [
                'data' => $verification,
                'message' => 'Verification request rejected successfully.',
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

}
