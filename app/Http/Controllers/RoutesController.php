<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Services\AccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoutesController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('module.cards');
    }

    public function create(AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user || !$accessService->canAccessModule($user, 'routes', 'edit')) {
            abort(403, 'Нет прав на создание маршрутов');
        }
        
        // Получаем доступные города в зависимости от роли
        $cities = $this->getAccessibleCities($user, $accessService);
        
        return view('routes.create', compact('cities', 'user'));
    }

    public function store(Request $request, AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user || !$accessService->canAccessModule($user, 'routes', 'edit')) {
            abort(403, 'Нет прав на создание маршрутов');
        }

        $data = $this->validateRoute($request, $user, $accessService);

        // Проверяем доступ к выбранному городу
        if (!empty($data['city_id']) && !$this->canAccessCity($user, $accessService, (int) $data['city_id'])) {
            abort(403, 'Нет доступа к выбранному городу');
        }

        $payload = [
            'route_code' => $data['route_code'],
            'route_type' => $data['route_type'],
        ];

        if (!empty($data['city_id'])) {
            $payload['city_id'] = (int) $data['city_id'];
        }

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

    public function edit(Route $route, AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        // Проверяем доступ к маршруту через город
        if ($route->city_id && !$this->canAccessCity($user, $accessService, $route->city_id)) {
            abort(403, 'Нет доступа к этому маршруту');
        }

        // Получаем доступные города в зависимости от роли
        $cities = $this->getAccessibleCities($user, $accessService);

        return view('routes.edit', compact('route', 'cities', 'user'));
    }

    public function update(Request $request, Route $route, AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        // Проверяем доступ к маршруту через город
        if ($route->city_id && !$this->canAccessCity($user, $accessService, $route->city_id)) {
            abort(403, 'Нет доступа к этому маршруту');
        }

        $data = $this->validateRoute($request, $user, $accessService);

        // Проверяем доступ к выбранному городу
        if (!empty($data['city_id']) && !$this->canAccessCity($user, $accessService, (int) $data['city_id'])) {
            abort(403, 'Нет доступа к выбранному городу');
        }

        $payload = [
            'route_code' => $data['route_code'],
            'route_type' => $data['route_type'],
        ];

        if (!empty($data['city_id'])) {
            $payload['city_id'] = (int) $data['city_id'];
        }

        if (schema_has_column('routes', 'route_area')) {
            $payload['route_area'] = $data['route_area'] ?? null;
        }

        $route->update($payload);

        return redirect()->route('module.cards')->with('ok', 'Маршрут обновлён');
    }

    public function importForm(AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user || !$accessService->canAccessModule($user, 'routes', 'edit')) {
            abort(403, 'Нет прав на импорт маршрутов');
        }

        // Получаем доступные города для фильтра
        $cities = collect();
        if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
                $cities = \App\Models\City::orderBy('city_name')->get();
            } elseif ($accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $cities = \App\Models\City::where('region_name', $region)->orderBy('city_name')->get();
                }
            }
        }

        return view('routes.import', compact('cities', 'user'));
    }

    public function import(Request $request, AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user || !$accessService->canAccessModule($user, 'routes', 'edit')) {
            abort(403, 'Нет прав на импорт маршрутов');
        }

        $validationRules = [
            'file' => ['required', 'file', 'mimes:csv,json,txt'],
            'file_type' => ['required', 'in:csv,json'],
        ];

        // Для developer, general_director, regional_director - обязательный выбор города
        if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            $validationRules['city_id'] = ['required', 'integer', 'exists:cities,city_id'];
        }

        $request->validate($validationRules);

        $file = $request->file('file');
        $fileType = $request->input('file_type');
        $columns = Schema::getColumnListing('routes');
        $imported = 0;
        $sortOrder = 1;
        
        DB::transaction(function () use ($file, $fileType, $columns, &$imported, &$sortOrder, $user, $accessService, $request) {
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

                    // Определяем city_id для импорта
                    $cityId = null;
                    
                    // Приоритет 1: city_id из формы (для developer, general_director, regional_director)
                    if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
                        $formCityId = $request->input('city_id');
                        if ($formCityId) {
                            // Проверяем доступ к городу
                            if ($accessService->isRegionalDirector($user)) {
                                $region = $accessService->regionName($user);
                                if ($region) {
                                    $cityExists = \App\Models\City::where('city_id', $formCityId)
                                        ->where('region_name', $region)
                                        ->exists();
                                    if ($cityExists) {
                                        $cityId = (int) $formCityId;
                                    }
                                }
                            } else {
                                // Developer и General Director - любой город
                                $cityId = (int) $formCityId;
                            }
                        }
                    }
                    
                    // Приоритет 2: city_id из CSV
                    if (!$cityId) {
                        $cityColumn = $map['city_id'] ?? null;
                        if ($cityColumn !== null && !empty($row[$cityColumn])) {
                            $cityId = (int) $row[$cityColumn];
                        }
                    }
                    
                    // Приоритет 3: определяем по роли (для остальных ролей)
                    if (!$cityId && $user) {
                        if ($accessService->isBranchScoped($user) && !empty($user->branch_id)) {
                            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
                        } elseif ($accessService->isRegionalDirector($user)) {
                            $region = $accessService->regionName($user);
                            if ($region) {
                                $cityId = \App\Models\City::where('region_name', $region)->value('city_id');
                            }
                        }
                    }

                    // Проверяем доступ к городу
                    if ($cityId && $user && !$this->canAccessCity($user, $accessService, $cityId)) {
                        continue; // Пропускаем маршрут, если нет доступа к городу
                    }

                    if ($cityId && schema_has_column('routes', 'city_id')) {
                        $payload['city_id'] = $cityId;
                    }

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

                    // Определяем city_id для импорта
                    $cityId = null;
                    
                    // Приоритет 1: city_id из формы (для developer, general_director, regional_director)
                    if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
                        $formCityId = $request->input('city_id');
                        if ($formCityId) {
                            // Проверяем доступ к городу
                            if ($accessService->isRegionalDirector($user)) {
                                $region = $accessService->regionName($user);
                                if ($region) {
                                    $cityExists = \App\Models\City::where('city_id', $formCityId)
                                        ->where('region_name', $region)
                                        ->exists();
                                    if ($cityExists) {
                                        $cityId = (int) $formCityId;
                                    }
                                }
                            } else {
                                // Developer и General Director - любой город
                                $cityId = (int) $formCityId;
                            }
                        }
                    }
                    
                    // Приоритет 2: city_id из JSON
                    if (!$cityId && !empty($item['city_id'])) {
                        $cityId = (int) $item['city_id'];
                    }
                    
                    // Приоритет 3: определяем по роли (для остальных ролей)
                    if (!$cityId && $user) {
                        if ($accessService->isBranchScoped($user) && !empty($user->branch_id)) {
                            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
                        } elseif ($accessService->isRegionalDirector($user)) {
                            $region = $accessService->regionName($user);
                            if ($region) {
                                $cityId = \App\Models\City::where('region_name', $region)->value('city_id');
                            }
                        }
                    }

                    // Проверяем доступ к городу
                    if ($cityId && $user && !$this->canAccessCity($user, $accessService, $cityId)) {
                        continue; // Пропускаем маршрут, если нет доступа к городу
                    }

                    if ($cityId && schema_has_column('routes', 'city_id')) {
                        $payload['city_id'] = $cityId;
                    }

                    $payload = array_intersect_key($payload, array_flip($columns));
                    Route::create($payload);

                    $imported++;
                    $sortOrder++;
                }
            }
        });

        return back()->with('ok', 'Импортировано: ' . $imported);
    }

    private function validateRoute(Request $request, $user = null, AccessService $accessService = null): array
    {
        $rules = [
            'route_code' => ['required', 'string', 'max:255'],
            'route_type' => ['required', 'in:city,private,mixed'],
        ];

        // Для developer и general_director city_id обязателен
        if ($user && $accessService && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user))) {
            $rules['city_id'] = ['required', 'integer', 'exists:cities,city_id'];
        } elseif ($user && $accessService) {
            // Для остальных - опционально, но если указан - проверяем
            $rules['city_id'] = ['nullable', 'integer', 'exists:cities,city_id'];
        }

        if (schema_has_column('routes', 'route_area')) {
            $rules['route_area'] = ['nullable', 'string', 'max:255'];
        }

        return $request->validate($rules);
    }

    private function getAccessibleCities($user, AccessService $accessService)
    {
        if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
            // Все города
            return \App\Models\City::orderBy('city_name')->get();
        } elseif ($accessService->isRegionalDirector($user)) {
            // Города своего региона
            $region = $accessService->regionName($user);
            if ($region) {
                return \App\Models\City::where('region_name', $region)->orderBy('city_name')->get();
            }
            return collect();
        } elseif ($accessService->isBranchDirector($user) && !empty($user->branch_id)) {
            // Город своего филиала
            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
            if ($cityId) {
                return \App\Models\City::where('city_id', $cityId)->get();
            }
            return collect();
        } elseif ($accessService->isManager($user) && !empty($user->branch_id)) {
            // Город своего филиала
            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
            if ($cityId) {
                return \App\Models\City::where('city_id', $cityId)->get();
            }
            return collect();
        }

        return collect();
    }

    private function canAccessCity($user, AccessService $accessService, int $cityId): bool
    {
        if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
            return true;
        } elseif ($accessService->isRegionalDirector($user)) {
            $region = $accessService->regionName($user);
            if ($region) {
                return \App\Models\City::where('city_id', $cityId)
                    ->where('region_name', $region)
                    ->exists();
            }
            return false;
        } elseif ($accessService->isBranchDirector($user) && !empty($user->branch_id)) {
            $userCityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
            return (int) $userCityId === $cityId;
        } elseif ($accessService->isManager($user) && !empty($user->branch_id)) {
            $userCityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
            return (int) $userCityId === $cityId;
        }

        return false;
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
