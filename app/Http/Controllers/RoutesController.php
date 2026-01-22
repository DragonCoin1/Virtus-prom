<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoutesController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('module.cards');
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

        if (schema_has_column('routes', 'sort_order')) {
            $payload['sort_order'] = (int) Route::max('sort_order') + 1;
        }

        Route::create($payload);

        return redirect()->route('module.cards')->with('ok', 'Маршрут добавлен');
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

        return redirect()->route('module.cards')->with('ok', 'Маршрут обновлён');
    }

    public function importForm()
    {
        return view('routes.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,json,txt'],
            'file_type' => ['required', 'in:csv,json'],
        ]);

        $file = $request->file('file');
        $fileType = $request->input('file_type');
        $columns = Schema::getColumnListing('routes');
        $imported = 0;
        $sortOrder = 1;

        DB::transaction(function () use ($file, $fileType, $columns, &$imported, &$sortOrder) {
            $this->clearRoutesData();

            if ($fileType === 'csv') {
                $handle = fopen($file->getRealPath(), 'r');
                if ($handle === false) {
                    throw new \Exception('Не удалось открыть файл');
                }

                $header = fgetcsv($handle, 0, ';');
                if (!$header) {
                    fclose($handle);
                    throw new \Exception('CSV файл пустой');
                }

                $header = array_map(fn($h) => trim((string) $h), $header);
                $map = array_flip($header);

                if (!isset($map['route_code'])) {
                    fclose($handle);
                    throw new \Exception('Не найдена колонка route_code');
                }

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
                        'sort_order' => $sortOrder,
                    ];

                    $payload = array_intersect_key($payload, array_flip($columns));
                    Route::create($payload);

                    $imported++;
                    $sortOrder++;
                }

                fclose($handle);
            } elseif ($fileType === 'json') {
                $jsonContent = file_get_contents($file->getRealPath());
                $data = json_decode($jsonContent, true);

                if (!is_array($data)) {
                    throw new \Exception('JSON файл некорректный. Ожидается массив объектов.');
                }

                foreach ($data as $item) {
                    if (!is_array($item) || empty($item['route_code'])) {
                        continue;
                    }

                    $payload = [
                        'route_code' => trim((string) $item['route_code']),
                        'route_district' => isset($item['route_district']) ? trim((string) $item['route_district']) : null,
                        'route_type' => $this->normalizeRouteType($item['route_type'] ?? null),
                        'boxes_count' => $this->normalizeInteger($item['boxes_count'] ?? null),
                        'entrances_count' => $this->normalizeInteger($item['entrances_count'] ?? null),
                        'is_active' => $this->normalizeBoolean($item['is_active'] ?? null),
                        'route_comment' => isset($item['route_comment']) ? trim((string) $item['route_comment']) : null,
                        'sort_order' => $sortOrder,
                    ];

                    $payload = array_intersect_key($payload, array_flip($columns));
                    Route::create($payload);

                    $imported++;
                    $sortOrder++;
                }
            }
        });

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

    private function clearRoutesData(): void
    {
        if (Schema::hasTable('route_action_templates')) {
            DB::table('route_action_templates')->delete();
        }

        if (Schema::hasTable('route_actions')) {
            DB::table('route_actions')->delete();
        }

        if (Schema::hasTable('routes')) {
            DB::table('routes')->delete();
        }
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
