<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
        $request->validate([
            'csv_file' => ['required', 'file'],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'Не удалось открыть файл']);
        }

        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV файл пустой']);
        }

        $header = array_map(fn($h) => trim((string) $h), $header);
        $map = array_flip($header);

        if (!isset($map['route_code'])) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Не найдена колонка route_code']);
        }

        $columns = Schema::getColumnListing('routes');
        $imported = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $row = array_map(fn($v) => trim((string) $v), $row);

            $code = $row[$map['route_code']] ?? '';
            if ($code === '') {
                continue;
            }

            $district = $map['route_district'] ?? null;
            $type = $map['route_type'] ?? null;
            $boxes = $map['boxes_count'] ?? null;
            $entrances = $map['entrances_count'] ?? null;
            $active = $map['is_active'] ?? null;
            $comment = $map['route_comment'] ?? null;

            $payload = [
                'route_code' => $code,
                'route_district' => $district !== null ? ($row[$district] ?? null) : null,
                'route_type' => $this->normalizeRouteType($type !== null ? ($row[$type] ?? null) : null),
                'boxes_count' => $boxes !== null ? $this->normalizeInteger($row[$boxes] ?? null) : 0,
                'entrances_count' => $entrances !== null ? $this->normalizeInteger($row[$entrances] ?? null) : 0,
                'is_active' => $active !== null ? $this->normalizeBoolean($row[$active] ?? null) : 1,
                'route_comment' => $comment !== null ? ($row[$comment] ?? null) : null,
            ];

            $payload = array_intersect_key($payload, array_flip($columns));

            Route::updateOrCreate(
                ['route_code' => $payload['route_code']],
                $payload
            );

            $imported++;
        }

        fclose($handle);

        return back()->with('ok', 'Импортировано: ' . $imported);
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

    private function normalizeRouteType(?string $value): string
    {
        $value = trim((string) $value);

        if (in_array($value, ['city', 'private', 'mixed'], true)) {
            return $value;
        }

        return 'city';
    }

    private function normalizeInteger(?string $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '.' || $value === '-') {
            return 0;
        }

        return (int) $value;
    }

    private function normalizeBoolean(?string $value): int
    {
        $value = strtolower(trim((string) $value));

        if ($value === '' || $value === '.' || $value === '-') {
            return 1;
        }

        return in_array($value, ['1', 'true', 'yes', 'да'], true) ? 1 : 0;
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
