<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class jwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {

            $user = JWTAuth::parseToken()->authenticate();

        } catch (TokenExpiredException $e) {

            return ApiResponse::error(
                ['token_expired'],
                'Token expired.',
                401
            );

        } catch (TokenInvalidException $e) {

            return ApiResponse::error(
                ['token_invalid'],
                'Token invalid.',
                401
            );

        } catch (JWTException $e) {

            return ApiResponse::error(
                ['token_missing'],
                'Token not provided.',
                401
            );
        }
        return $next($request);
    }
}
