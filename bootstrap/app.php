<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e,$request) {

            $validator = $e->validator;

//            $errors = collect($validator->failed())
//                ->map(function ($rules, $field) {
//                    return array_map('strtolower', array_keys($rules));
//                })
//                ->toArray();

            $errors = collect($validator->failed())
                ->map(fn ($rules) => array_map('strtolower', array_keys($rules)))
                ->toArray();

            return ApiResponse::validation(
                $errors,
                'Validation Error.'
            );
        });

        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e,$request) {
            return ApiResponse::error([$e->getMessage()],'Authentication Error.',401);
        });

        $exceptions->renderable(function(AccessDeniedHttpException $e, $request) {
            // that's for when someone has no permission to do smth we need to customize the message for fornt_end
            return ApiResponse::error(
                [],
                'you do not have the authorization to access this page.',
                403
            );
        });
        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            return ApiResponse::error(
                ['route_not_found'],
                'Route not found.',
                404
            );

        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, $request) {
            return ApiResponse::error(
                ['method_not_allowed'],
                'Method not allowed.',
                405
            );
        });

    })->create();
