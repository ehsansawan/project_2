<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\jwtMiddleware;
use App\Http\Middleware\VerifiedEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    $user = User::findOrFail($request->route('id'));

    if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Invalid verification link'], 403);
    }


    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified'], 200);
    }

    $user->markEmailAsVerified();
    event(new Verified($user));
    //this two line is instead of $request->fullfill() because it redirect to route login


    return response()->json(['message' => 'Email verified successfully'], 200);
})
    ->middleware(['signed'])->name('verification.verify');

// resend verification email
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return \App\Http\Responses\ApiResponse::success(true,'Verification link sent!');
})
    ->middleware([jwtMiddleware::class,'throttle:2,1'])->name('verification.send');

//auth
Route::controller(AuthController::class)->prefix('auth')
    ->name('auth.')
    ->group(function () {

        Route::post('/register', 'register')->name('register');
        Route::post('/login', 'login')->name('login');
        Route::middleware('auth:api')->post('/logout', 'logout')->name('logout');
        Route::middleware(jwtMiddleware::class)->post('/refresh', 'refresh')->name('refresh')->middleware(VerifiedEmail::class);
        Route::post('/forgetPassword','forgetPassword')->name('forgetPassword');
        Route::post('/resetPassword','resetPassword')->name('resetPassword');
        Route::post('/checkCode','checkCode')->name('checkCode');
    });

Route::middleware([jwtMiddleware::class,])->group(function () {

    // Verification_request
    Route::controller(VerificationController::class)->prefix('verification')
        ->name('verification.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/store', 'store')->name('store');
            Route::post('/update', 'update')->name('update');
//            Route::delete('/{id}', 'destroy')->name('destroy');

            // Admin routes

            Route::post('/adminIndex', 'adminIndex')->name('adminIndex');
            Route::get('/adminShow/{id}', 'adminShow')->name('adminShow');
            Route::get('adminIndexByUser/{user_id}','adminIndexByUser')->name('adminIndexByUser');
            Route::get('/approve/{id}', 'approve')->name('approve');
            Route::post('/reject/{id}', 'reject')->name('reject');

        });

    Route::controller(ProfileController::class)->prefix('profile')
        ->name('profile.')
        ->group(function () {
            Route::get('/','index')->name('index');
            Route::get('/show/{id}', 'show')->name('show');
            Route::post('/update', 'update')->name('update');
        });


});


