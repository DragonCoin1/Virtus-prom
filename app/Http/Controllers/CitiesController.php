<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Services\AccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CitiesController extends Controller
{
    public function index(Request $request)
    {
        $q = City::query();

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $q->where(function ($query) use ($s) {
                $query->where('city_name', 'like', '%' . $s . '%')
                    ->orWhere('region_name', 'like', '%' . $s . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $q->where('is_active', 1);
            }
            if ($request->input('status') === 'inactive') {
                $q->where('is_active', 0);
            }
        }

        if ($request->filled('min_population')) {
            $minPop = (int) $request->input('min_population');
            $q->where('population', '>=', $minPop);
        }

        $cities = $q->orderBy('city_name')
            ->paginate(30)
            ->appends($request->query());

        return view('cities.index', compact('cities'));
    }

    public function create(AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user || !(
            $accessService->isDeveloper($user) || 
            $accessService->isGeneralDirector($user) || 
            $accessService->isRegionalDirector($user)
        )) {
            abort(403, 'Нет прав на добавление городов');
        }
        
        return view('cities.create');
    }

    public function store(Request $request, AccessService $accessService)
    {
        $user = auth()->user();
        if (!$user || !(
            $accessService->isDeveloper($user) || 
            $accessService->isGeneralDirector($user) || 
            $accessService->isRegionalDirector($user)
        )) {
            abort(403, 'Нет прав на добавление городов');
        }

        $request->validate([
            'city_name' => ['required', 'string', 'max:255'],
            'region_name' => ['nullable', 'string', 'max:255'],
            'population' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        City::updateOrCreate(
            ['city_name' => trim($request->input('city_name'))],
            [
                'region_name' => $request->filled('region_name') ? trim($request->input('region_name')) : null,
                'population' => $request->filled('population') ? (int) $request->input('population') : null,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ]
        );

        return redirect()->route('cities.index')->with('ok', 'Город успешно добавлен');
    }

    public function importForm()
    {
        return view('cities.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,json'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'json') {
            return $this->importJson($file);
        }

        return $this->importCsv($file);
    }

    private function importCsv($file)
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => 'Не удалось открыть файл']);
        }

        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['file' => 'CSV файл пустой']);
        }

        $header = array_map(fn($h) => trim((string) $h), $header);
        $map = array_flip($header);

        $requiredColumns = ['city_name'];
        foreach ($requiredColumns as $col) {
            if (!isset($map[$col])) {
                fclose($handle);
                return back()->withErrors(['file' => "Не найдена обязательная колонка: {$col}"]);
            }
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) === 1 && trim((string) $row[0]) === '') {
                    continue;
                }

                $row = array_map(fn($v) => trim((string) $v), $row);

                $cityName = $row[$map['city_name']] ?? '';
                if ($cityName === '') {
                    $skipped++;
                    continue;
                }

                $regionName = isset($map['region_name']) ? ($row[$map['region_name']] ?? null) : null;
                $population = isset($map['population']) ? $this->normalizeInteger($row[$map['population']] ?? null) : null;
                $isActive = isset($map['is_active']) ? $this->normalizeBoolean($row[$map['is_active']] ?? null) : true;

                City::updateOrCreate(
                    ['city_name' => $cityName],
                    [
                        'region_name' => $regionName ?: null,
                        'population' => $population,
                        'is_active' => $isActive ? 1 : 0,
                    ]
                );

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->withErrors(['file' => 'Ошибка при импорте: ' . $e->getMessage()]);
        }

        fclose($handle);

        $message = "Импортировано: {$imported}";
        if ($skipped > 0) {
            $message .= ", пропущено: {$skipped}";
        }

        return back()->with('ok', $message)->with('errors', $errors);
    }

    private function importJson($file)
    {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['file' => 'Неверный формат JSON: ' . json_last_error_msg()]);
        }

        if (!is_array($data)) {
            return back()->withErrors(['file' => 'JSON должен содержать массив объектов']);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($data as $item) {
                if (!is_array($item)) {
                    $skipped++;
                    continue;
                }

                $cityName = trim($item['city_name'] ?? $item['name'] ?? '');
                if ($cityName === '') {
                    $skipped++;
                    continue;
                }

                $regionName = isset($item['region_name']) ? trim($item['region_name']) : null;
                $population = isset($item['population']) ? $this->normalizeInteger($item['population']) : null;
                $isActive = isset($item['is_active']) ? $this->normalizeBoolean($item['is_active']) : true;

                City::updateOrCreate(
                    ['city_name' => $cityName],
                    [
                        'region_name' => $regionName ?: null,
                        'population' => $population,
                        'is_active' => $isActive ? 1 : 0,
                    ]
                );

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'Ошибка при импорте: ' . $e->getMessage()]);
        }

        $message = "Импортировано: {$imported}";
        if ($skipped > 0) {
            $message .= ", пропущено: {$skipped}";
        }

        return back()->with('ok', $message)->with('errors', $errors);
    }

    private function normalizeInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/[^\d]/', '', $value);

        if ($value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeBoolean($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $value = trim((string) $value);
        $value = strtolower($value);

        return in_array($value, ['1', 'true', 'yes', 'да', 'y'], true);
    }
}
