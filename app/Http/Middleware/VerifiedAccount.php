<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifiedAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->account_status !== AccountStatus::Verified->value) {
            return ApiResponse::error(
                ['error'=>'account_not_verified'],
                'Only verified accounts can access this resource.',
                403
            );
        }

        return $next($request);
    }
}
