<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $module, string $permission = 'view'): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $access = DB::table('role_module_access')
            ->where('role_id', $user->role_id)
            ->where('module_code', $module)
            ->first();

        if (!$access) {
            abort(403, 'Нет доступа к разделу');
        }

        if ($permission === 'edit' && (int)$access->can_edit !== 1) {
            abort(403, 'Нет права редактирования');
        }

        if ($permission === 'view' && (int)$access->can_view !== 1) {
            abort(403, 'Нет права просмотра');
        }

        return $next($request);
    }
}
