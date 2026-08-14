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
use App\Http\Controllers\ComplainController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\AdminQueueController;
use App\Http\Controllers\ArchiveStatisticsController;
use App\Http\Controllers\UserSkillController;
use App\Http\Controllers\UserCertificateController;




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
        Route::post('/refresh', 'refresh')->name('refresh');
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
            Route::post('/SearchByNationalId','SearchByNationalId')->name('SearchByNationalId');

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

    // User skills routes
    Route::controller(UserSkillController::class)->prefix('skill')
        ->name('skill.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/store', 'store')->name('store');
            Route::post('/update/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
        });

    // User certificates routes
    Route::controller(UserCertificateController::class)->prefix('certificate')
        ->name('certificate.')
        ->group(function () {
            // for users
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/store', 'store')->name('store');
            Route::post('/update/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');

            // for admins
            Route::post('/adminIndex', 'getCertificates')->name('adminIndex');
            Route::get('/adminShow/{id}', 'getCertificate')->name('adminShow');
            Route::get('/adminIndexByUser/{user_id}', 'getUserCertificates')->name('adminIndexByUser');
            Route::get('/approve/{id}', 'approve')->name('approve');
            Route::post('/reject/{id}', 'reject')->name('reject');
        });


});



    //complains routes
    Route::controller(ComplainController::class)
        ->prefix("complains")
        ->group(function () {
            Route::middleware(jwtMiddleware::class)->post('/', 'store')->name('store')->middleware(VerifiedEmail::class);
            Route::get("/","index");
            Route::get("/{complainId}","show");
            Route::put("/{complainId}","update");
            Route::delete("/{complainId}","destroy");
    });

    Route::controller(ReportController::class)
        ->prefix("reports")
        ->group(function(){
            Route::middleware(jwtMiddleware::class)->post('/', 'store')->name('store')->middleware(VerifiedEmail::class);
        });


    // مسارات قائمة الانتظار
    Route::prefix('queue')->group(function () {
        Route::get('/services/{serviceId}', [QueueController::class, 'showService']);
        Route::post('/join', [QueueController::class, 'join']);
    });

    // مسارات عامة (للمواطنين - بدون تسجيل دخول)
    Route::prefix('queue')->group(function () {
        Route::get('/info/{qrCodeString}', [ServiceController::class, 'getQueueInfo']);
    });

    // مسارات الأدمن (لإدارة الخدمات - تحتاج تسجيل دخول + صلاحيات موظف)
    Route::middleware([jwtMiddleware::class, VerifiedEmail::class])->prefix('admin/services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('/{id}', [ServiceController::class, 'show']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{id}', [ServiceController::class, 'update']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
        Route::post('/{serviceId}/assign-employee', [ServiceController::class, 'assignEmployee']);
        Route::delete('/{serviceId}/unassign-employee/{employeeId}', [ServiceController::class, 'unassignEmployee']);
    });

    // مسارات إدارة قائمة الانتظار (للمسؤولين - تحتاج تسجيل دخول + صلاحيات موظف)
    Route::middleware([jwtMiddleware::class, VerifiedEmail::class])->prefix('admin/queue')->group(function () {
        Route::get('/{serviceId}/dashboard', [AdminQueueController::class, 'dashboard']);
        Route::post('/{serviceId}/add-manual', [AdminQueueController::class, 'addManual']);
        Route::post('/{serviceId}/call-next', [AdminQueueController::class, 'callNext']);

        Route::post('/tickets/{ticketId}/serve', [AdminQueueController::class, 'markAsServed']);
        Route::post('/tickets/{ticketId}/no-show', [AdminQueueController::class, 'markAsNoShow']);
        Route::post('/tickets/{ticketId}/return', [AdminQueueController::class, 'returnToQueue']);
    });



    // مسارات إحصائيات قائمة الانتظار المؤرشفة (للمسؤولين - تحتاج تسجيل دخول + صلاحيات موظف)
    Route::middleware([jwtMiddleware::class, VerifiedEmail::class])->prefix('admin/queue/statistics')->group(function () {
        Route::get('/overview', [ArchiveStatisticsController::class, 'overview']);
        Route::get('/services/{serviceId}', [ArchiveStatisticsController::class, 'serviceStats']);
        Route::get('/employees', [ArchiveStatisticsController::class, 'employeeStats']);
        Route::get('/history', [ArchiveStatisticsController::class, 'history']);
    });
