<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
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
use App\Http\Controllers\AdminComplainController;
use App\Http\Controllers\ProjectController;




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
        Route::post('/admin/login','loginAdmin')->name('loginAdmin');
        Route::middleware('auth:api')->
        post('/logout', 'logout')->name('logout');
        Route::post('/refresh', 'refresh')->name('refresh');
        Route::post('/forgetPassword','forgetPassword')->name('forgetPassword');
        Route::post('/resetPassword','resetPassword')->name('resetPassword');
        Route::post('/checkCode','checkCode')->name('checkCode');
    });

Route::middleware([jwtMiddleware::class,])->group(function () {

    //user_request
    Route::controller(UserController::class)
        ->prefix('user')
        ->name('user.')
        ->group(function () {
        Route::post('/', 'index')->name('index')->middleware('can:user.index');
        Route::post('/create', 'create')->name('create')->middleware('can:user.create');
        Route::post('/createAdmin', 'createAdmin')->name('createAdmin')->middleware('can:user.createAdmin');
        Route::post('/update', 'update')->name('update')->middleware('can:user.update');
        Route::post('/changePassword', 'changePassword')->name('changePassword')->middleware('can:user.changePassword');
        Route::delete('/destroy', 'destroy')->name('destroy')->middleware('can:user.destroy');

        });

    // Verification_request
    Route::controller(VerificationController::class)->prefix('verification')
        ->name('verification.')
        ->group(function () {
            Route::get('/', 'index')->name('index')->middleware('can:verification.index');
            Route::get('/{id}', 'show')->name('show')->middleware('can:verification.show');
            Route::post('/store', 'store')->name('store')->middleware('can:verification.store');
            Route::post('/update', 'update')->name('update')->middleware('can:verification.update');
            Route::post('/SearchByNationalId','SearchByNationalId')->name('SearchByNationalId')->middleware('can:verification.SearchByNationalId');

//            Route::delete('/{id}', 'destroy')->name('destroy');

            // Admin routes

            Route::post('/adminIndex', 'adminIndex')->name('adminIndex')->middleware('can:verification.adminIndex');
            Route::get('/adminShow/{id}', 'adminShow')->name('adminShow')->middleware('can:verification.adminShow');
            Route::get('adminIndexByUser/{user_id}','adminIndexByUser')->name('adminIndexByUser')->middleware('can:verification.adminIndexByUser');
            Route::get('/approve/{id}', 'approve')->name('approve')->middleware('can:verification.approve');
            Route::post('/reject/{id}', 'reject')->name('reject')->middleware('can:verification.reject');

        });

    Route::controller(ProfileController::class)->prefix('profile')
        ->name('profile.')
        ->group(function () {
            Route::get('/','index')->name('index')->middleware('can:profile.index');
            Route::get('/show/{id}', 'show')->name('show')->middleware('can:profile.show');
            Route::post('/update', 'update')->name('update')->middleware('can:profile.update');
            Route::delete('/avatar', 'destroyAvatar')->name('destroyAvatar')->middleware('can:profile.destroyAvatar');
        });

    // User skills routes
    Route::controller(UserSkillController::class)->prefix('skill')
        ->name('skill.')
        ->group(function () {
            Route::get('/', 'index')->name('index')->middleware('can:skill.index');
            Route::get('/{id}', 'show')->name('show')->middleware('can:skill.show');
            Route::post('/store', 'store')->name('store')->middleware('can:skill.store');
            Route::post('/update/{id}', 'update')->name('update')->middleware('can:skill.update');
            Route::delete('/{id}', 'destroy')->name('destroy')->middleware('can:skill.destroy');
        });

    // Project routes (employee)
    Route::controller(ProjectController::class)->prefix('project')
        ->name('project.')
        ->group(function () {
            Route::get('/', 'index')->name('index')->middleware('can:project.index');
            Route::get('/{id}', 'show')->name('show')->middleware('can:project.show');
            Route::post('/create', 'store')->name('create')->middleware('can:project.create');
            Route::post('/submit/{id}', 'submitForReview')->name('submitForReview')->middleware('can:project.submitForReview');
            Route::post('/approve/{id}', 'approve')->name('approve')->middleware('can:project.approve');
            Route::post('/reject/{id}', 'reject')->name('reject')->middleware('can:project.reject');
            Route::post('/update/{id}', 'update')->name('update')->middleware('can:project.update');
            Route::delete('/{id}', 'destroy')->name('destroy')->middleware('can:project.destroy');
        });

    // User certificates routes
    Route::controller(UserCertificateController::class)->prefix('certificate')
        ->name('certificate.')
        ->group(function () {
            // for users
            Route::get('/', 'index')->name('index')->middleware('can:certificate.index');
            Route::get('/{id}', 'show')->name('show')->middleware('can:certificate.show');
            Route::post('/store', 'store')->name('store')->middleware('can:certificate.store');
            Route::post('/update/{id}', 'update')->name('update')->middleware('can:certificate.update');
            Route::delete('/{id}', 'destroy')->name('destroy')->middleware('can:certificate.destroy');

            // for admins
            Route::post('/adminIndex', 'getCertificates')->name('adminIndex')->middleware('can:certificate.adminIndex');
            Route::get('/adminShow/{id}', 'getCertificate')->name('adminShow')->middleware('can:certificate.adminShow');
            Route::get('/adminIndexByUser/{user_id}', 'getUserCertificates')->name('adminIndexByUser')->middleware('can:certificate.adminIndexByUser');
            Route::get('/approve/{id}', 'approve')->name('approve')->middleware('can:certificate.approve');
            Route::post('/reject/{id}', 'reject')->name('reject')->middleware('can:certificate.reject');
        });


});



    //complains routes
    Route::controller(ComplainController::class)
        ->prefix("complains")
        ->name('complain.')
        ->group(function () {
            Route::middleware(jwtMiddleware::class)->post('/', 'store')->name('store')->middleware([VerifiedEmail::class, 'can:complain.store']);
            Route::get('/complains', [ComplainController::class, 'index'])->middleware([jwtMiddleware::class, VerifiedEmail::class, 'can:complain.index'])->name('index');
            Route::get('/my-complains', [ComplainController::class, 'myComplains'])->middleware([jwtMiddleware::class, VerifiedEmail::class, 'can:complain.myComplains'])->name('myComplains');

            // التصويت
            Route::post('/vote', [ComplainController::class, 'vote'])->middleware([jwtMiddleware::class, VerifiedEmail::class, 'can:complain.vote'])->name('vote');
            Route::delete('/vote', [ComplainController::class, 'unvote'])->middleware([jwtMiddleware::class, VerifiedEmail::class, 'can:complain.unvote'])->name('unvote');
         //   Route::get("/","index")->name('index');
            Route::get("/{complainId}","show")->name('show')->middleware([jwtMiddleware::class, 'can:complain.show']);
            Route::put("/{complainId}","update")->name('update')->middleware([jwtMiddleware::class, 'can:complain.update']);
            Route::delete("/{complainId}","destroy")->name('destroy')->middleware([jwtMiddleware::class, 'can:complain.destroy']);
    });

    Route::controller(ReportController::class)
        ->prefix("reports")
        ->name('report.')
        ->group(function(){
            Route::middleware(jwtMiddleware::class)->post('/', 'store')->name('store')->middleware([VerifiedEmail::class, 'can:report.store']);
        });


    // مسارات قائمة الانتظار
    Route::middleware(jwtMiddleware::class)->prefix('queue')
        ->name('queue.')
        ->group(function () {
        Route::get('/services/{serviceId}', [QueueController::class, 'showService'])->name('showService');
        Route::post('/join', [QueueController::class, 'join'])->name('join');
        Route::get('/info/{qrCodeString}', [ServiceController::class, 'getQueueInfo'])->name('info');
    });

    // مسارات الأدمن (لإدارة الخدمات - تحتاج تسجيل دخول + صلاحيات موظف)
    Route::middleware([jwtMiddleware::class, VerifiedEmail::class])->prefix('admin/services')
        ->name('admin.services.')
        ->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index')->middleware('can:admin.services.index');
        Route::get('/{id}', [ServiceController::class, 'show'])->name('show')->middleware('can:admin.services.show');
        Route::post('/', [ServiceController::class, 'store'])->name('store')->middleware('can:admin.services.store');
        Route::put('/{id}', [ServiceController::class, 'update'])->name('update')->middleware('can:admin.services.update');
        Route::delete('/{id}', [ServiceController::class, 'destroy'])->name('destroy')->middleware('can:admin.services.destroy');
        Route::post('/{serviceId}/assign-employee', [ServiceController::class, 'assignEmployee'])->name('assignEmployee')->middleware('can:admin.services.assignEmployee');
        Route::delete('/{serviceId}/unassign-employee/{employeeId}', [ServiceController::class, 'unassignEmployee'])->name('unassignEmployee')->middleware('can:admin.services.unassignEmployee');
    });

    // مسارات إدارة قائمة الانتظار (للمسؤولين - تحتاج تسجيل دخول + صلاحيات موظف)
    Route::middleware([jwtMiddleware::class, VerifiedEmail::class])->prefix('admin/queue')
        ->name('admin.queue.')
        ->group(function () {
        Route::get('/{serviceId}/dashboard', [AdminQueueController::class, 'dashboard'])->name('dashboard')->middleware('can:admin.queue.dashboard');
        Route::post('/{serviceId}/add-manual', [AdminQueueController::class, 'addManual'])->name('addManual')->middleware('can:admin.queue.addManual') ;
        Route::post('/{serviceId}/call-next', [AdminQueueController::class, 'callNext'])->name('callNext')->middleware('can:admin.queue.callNext') ;

        Route::post('/tickets/{ticketId}/serve', [AdminQueueController::class, 'markAsServed'])->name('markAsServed')->middleware('can:admin.queue.markAsServed') ;
        Route::post('/tickets/{ticketId}/no-show', [AdminQueueController::class, 'markAsNoShow'])->name('markAsNoShow')->middleware('can:admin.queue.markAsNoShow') ;
        Route::post('/tickets/{ticketId}/return', [AdminQueueController::class, 'returnToQueue'])->name('returnToQueue')->middleware('can:admin.queue.returnToQueue') ;
    });



    // مسارات إحصائيات قائمة الانتظار المؤرشفة (للمسؤولين - تحتاج تسجيل دخول + صلاحيات موظف)
    Route::middleware([jwtMiddleware::class, VerifiedEmail::class])->prefix('admin/queue/statistics')
        ->name('admin.queue.statistics.')
        ->group(function () {
        Route::get('/overview', [ArchiveStatisticsController::class, 'overview'])->name('overview')->middleware('can:admin.queue.statistics.overview') ;
        Route::get('/services/{serviceId}', [ArchiveStatisticsController::class, 'serviceStats'])->name('serviceStats')->middleware('can:admin.queue.statistics.serviceStats') ;
        Route::get('/employees', [ArchiveStatisticsController::class, 'employeeStats'])->name('employeeStats')->middleware('can:admin.queue.statistics.employeeStats') ;
        Route::get('/history', [ArchiveStatisticsController::class, 'history'])->name('history')->middleware('can:admin.queue.statistics.history') ;
    });



    // مسارات إدارة الشكاوى (للمسؤولين - تحتاج تسجيل دخول + صلاحيات موظف)
    Route::middleware([jwtMiddleware::class, VerifiedEmail::class])->prefix('admin/complains')
        ->name('admin.complains.')
        ->group(function () {
        Route::get('/', [AdminComplainController::class, 'index'])->name('index')->middleware('can:admin.complains.index');
        Route::get('/{id}', [AdminComplainController::class, 'show'])->name('show')->middleware('can:admin.complains.show');
        Route::put('/{id}/review', [AdminComplainController::class, 'review'])->name('review')->middleware('can:admin.complains.review') ;
        Route::put('/{id}/status', [AdminComplainController::class, 'updateStatus'])->name('status')->middleware('can:admin.complains.status') ;
    });
