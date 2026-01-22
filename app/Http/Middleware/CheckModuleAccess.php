<?php

namespace App\Http\Middleware;

use App\Services\AccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $module, string $permission = 'view'): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $accessService = app(AccessService::class);
        $permission = $permission === 'delete' ? 'edit' : $permission;

        if (!$accessService->canAccessModule($user, $module, $permission)) {
            abort(403, $permission === 'view' ? 'Нет права просмотра' : 'Нет права редактирования');
        }

        return $next($request);
    }
}
