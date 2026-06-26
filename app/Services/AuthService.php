<?php

namespace App\Services;


use App\Mail\SendCodeResetPassword;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;



class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //

    }

    public function register($request):array
    {

        $user=User::query()->create([

            'first_name'=>$request['first_name']??null,
            'last_name'=>$request['last_name']??null,
            'email'=>$request['email']??null,
            'phone'=>$request['phone']??null,
            'national_id'=>$request['national_id']??null,
            'password'=>Hash::make($request['password']),
            'birth_date'=>$request['birth_date'],
            'fcm_token'=>$request['fcm_token']??null,
        ]);

        if(!$user)
        {
            $message="something went wrong,try again later";
            $code=500;
            return ["user"=>$user,"message"=>$message,"code"=>$code];
        }

        // for email verification
        //this function send email the $request['email']
         event(new Registered($user));



        $message='user registered successfully';
        $code=201;

        //jwt auth
        $token=auth('api')->login($user);
        $user['token']=$token;
        $user['token_type']='bearer';
        return ['user'=>$user,'message'=>$message,'code'=>$code];

    }
    public function login($request):array
    {

        $credentials = ['email'=>$request['email'],'password'=>$request['password']];
        $token = Auth('api')->attempt($credentials);

        if (!$token) {
            $user=null;
            $message = 'your email or password is wrong';
            $code=401;
            return ['user'=>$user,'message'=>$message,'code'=>$code];
        }

        $user = Auth('api')->user();
     //   $user->fcm_token=$request['fcm_token']??$user->fcm_token;
        $user->save();
        $user['token']=  $token;
        $user['token_type']=  'Bearer';
        $message = 'User logged in successfully';
        $code=200;




        return ['user'=>$user,'message'=>$message,'code'=>$code];

    }
    public function logout():array
    {
        $user=Auth('api')->user();
        if(!is_null($user))
        {
            auth('api')->logout();
         //   $user->fcm_token=null;
            $user->save();
            $message='user logged out successfully';
            $code=200;
        }
        else
        {
            $message='invalid token';
            $code=404;
        }
        return ['user'=>$user,'message'=>$message,'code'=>$code];
    }
    public function refresh(Request $request):array
    {

        // اجلب التوكن من الهيدر
        $token = $request->bearerToken();


        if (!$token) {
            $user=null;
            $message = 'Token not provided';
            $code=401;
            return ['user'=>$user,'message'=>$message,'code'=>$code];
        }

        // استخدم الجارد jwt مباشرة
        $newToken = auth('api')->setToken($token)->refresh();
        $user=auth('api')->user();
        $user['token']=  $newToken;
        $user['token_type']=  'Bearer';
        $message='User Token refreshed successfully';
        $code=200;


        // مدة صلاحية التوكن (بالدقائق * 60 = ثواني)
        //  $ttl = auth('api')->factory()->getTTL();
        // there is a problem with this code

        return [
            'user'=>$user,'message'=>$message,'code'=>$code
            // 'expires_in' => $ttl * 60,
        ];

    }
    public function forgetPassword(Request $request):array
    {
        $input=$request->validate([
            'email'=>'required|email|exists:users,email',
        ]);

        $hourlyKey = 'forgot-password:' . $input['email'];
        $cooldownKey = 'otp-cooldown:' . $input['email'];

        // Maximum 5 requests per hour
        if (RateLimiter::tooManyAttempts($hourlyKey, 5)) {
            throw ValidationException::withMessages([
                'rate_limit' => 'Too many attempts.',
            ]);
        }

        // One request every 60 seconds
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            throw ValidationException::withMessages([
                'rate_limit' => 'Please wait before requesting another code.',
            ]);
        }




        //Delete all old code that user send before
        ResetCodePassword::query()->where('email','=',$input['email'])->delete();

        //Generate random code
        $input['code']=mt_rand(100000,999999);

        //create a new code
        ResetCodePassword::query()->create([
            'email'=>$input['email'],
            'code'=>$input['code'],
        ]);

        //send email to user
        Mail::to($input['email'])->send(new SendCodeResetPassword($input['code']));

        // Increment rate limit counters AFTER successful send
        RateLimiter::hit($hourlyKey, 3600); // 1 hour
        RateLimiter::hit($cooldownKey, 60); // 60 seconds


        $message='we send you an email,check your mails';
        $code=200;

        return ['info'=>['email'=>$input['email']],'message'=>$message,'code'=>$code];
    }
    public function checkCode(Request $request):array
    {
        $input=$request->validate([
            'code'=>'required|string|exists:reset_code_passwords,code',
        ]);

        //find the code
        $passwordReset=ResetCodePassword::query()->where('code','=',$input['code'])->first();
        //check if the code expired
        if($passwordReset['created_at'] > now()->addHour())
        {
            $passwordReset->delete();
            $message='code has expired';
            $code=400;
            return ['info'=>$input,'message'=>$message,'code'=>$code];
        }

        $message='code is valid';
        $code=200;
        return ['info'=>$input,'message'=>$message,'code'=>$code];

    }
    public function resetPassword( $request):array
    {

        //find the code
        $passwordReset=ResetCodePassword::query()->where('code','=',$request['code'])->first();
        //check if the code expired
        if($passwordReset['created_at'] > now()->addHour())
        {
            $passwordReset->delete();
            $message='code has expired';
            $code=400;
            return ['info'=>$request,'message'=>$message,'code'=>$code];
        }

        $user=User::query()->where('email','=',$passwordReset['email'])->first();

        //remember to check if the user exists

        if(!$user)
        {
            $message='email not found';
            $code=404;
            return ['info'=>$request,'message'=>$message,'code'=>$code];
        }

        // update user password
        $user->update(['password'=>bcrypt($request['password'])]);
      //  $user->fcm_token=$request['fcm_token']??$user->fcm_token;
        $user->save();


        //delete current code
        ResetCodePassword::query()->where('code','=',$request['code'])->delete();

        $credentials = ['email'=>$user['email'],'password'=>$request['password']];

        $token = Auth('api')->attempt($credentials);

        if (!$token) {
            $user=null;
            $message = 'your email or password is wrong';
            $code=401;
        }

        $user = Auth('api')->user();
        $user['token']=  $token;
        $user['token_type']=  'Bearer';

        $input=$user;


        $message='password reset successfully';
        $code=200;
        return ['info'=>$input,'message'=>$message,'code'=>$code];


    }




}
