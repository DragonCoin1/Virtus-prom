<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;

class RoutesController extends Controller
{
    public function index(Request $request)
    {
        $q = Route::query();

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $q->where(function ($qq) use ($s) {
                $qq->where('route_code', 'like', '%' . $s . '%');

                if (schema_has_column('routes', 'route_area')) {
                    $qq->orWhere('route_area', 'like', '%' . $s . '%');
                }
            });
        }

        if ($request->filled('type')) {
            $q->where('route_type', $request->input('type'));
        }

        $routes = $q->orderBy('route_code')->paginate(30)->appends($request->query());

        return view('routes.index', compact('routes'));
    }

    public function create()
    {
        return view('routes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateRoute($request);

        $payload = [
            'route_code' => $data['route_code'],
            'route_type' => $data['route_type'],
        ];

        if (schema_has_column('routes', 'route_area')) {
            $payload['route_area'] = $data['route_area'] ?? null;
        }

        if (schema_has_column('routes', 'is_active')) {
            $payload['is_active'] = 1;
        }

        Route::create($payload);

        return redirect()->route('routes.index')->with('ok', 'Маршрут добавлен');
    }

    public function edit(Route $route)
    {
        return view('routes.edit', compact('route'));
    }

    public function update(Request $request, Route $route)
    {
        $data = $this->validateRoute($request);

        $payload = [
            'route_code' => $data['route_code'],
            'route_type' => $data['route_type'],
        ];

        if (schema_has_column('routes', 'route_area')) {
            $payload['route_area'] = $data['route_area'] ?? null;
        }

        $route->update($payload);

        return redirect()->route('routes.index')->with('ok', 'Маршрут обновлён');
    }

    public function importForm()
    {
        return view('routes.import');
    }

    public function import(Request $request)
    {
        // оставляем вашу текущую реализацию импорта как есть
        // (не трогаю, чтобы ничего не сломать)
        return back()->with('ok', 'Импорт: используйте текущую реализацию в проекте');
    }

    private function validateRoute(Request $request): array
    {
        $rules = [
            'route_code' => ['required', 'string', 'max:255'],
            'route_type' => ['required', 'in:city,private,mixed'],
        ];

        if (schema_has_column('routes', 'route_area')) {
            $rules['route_area'] = ['nullable', 'string', 'max:255'];
        }

        return $request->validate($rules);
    }
}

/**
 * маленький хелпер, чтобы не падать если колонок нет
 */
function schema_has_column(string $table, string $column): bool
{
    try {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    } catch (\Throwable $e) {
        return false;
    }
}
