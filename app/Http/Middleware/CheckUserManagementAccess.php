<?php

namespace App\Http\Middleware;

use App\Services\AccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserManagementAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $accessService = app(AccessService::class);

        if ($accessService->isPromoter($user)) {
            abort(403, 'Нет доступа к управлению учётными записями');
        }

        return $next($request);
    }
}
