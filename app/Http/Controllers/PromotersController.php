<?php

namespace App\Http\Controllers;

use App\Models\Promoter;
use App\Services\AccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PromotersController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $q = Promoter::query();
        $user = $request->user();
        if ($user) {
            $accessService->scopePromoters($q, $user);
        }

        // Фильтр по городу (для developer, general_director, regional_director)
        $cityId = $request->input('city_id');
        if ($cityId && $user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            // Проверяем доступ к городу
            if ($accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $cityExists = \App\Models\City::where('city_id', $cityId)
                        ->where('region_name', $region)
                        ->exists();
                    if ($cityExists) {
                        $q->whereHas('branch', function ($query) use ($cityId) {
                            $query->where('city_id', $cityId);
                        });
                    }
                }
            } else {
                // Developer и General Director - любой город
                $q->whereHas('branch', function ($query) use ($cityId) {
                    $query->where('city_id', $cityId);
                });
            }
        }

        // Поиск (ФИО + телефон)
        $search = $request->input('search', $request->input('q'));
        if (!empty($search)) {
            $s = trim((string) $search);
            $q->where(function ($qq) use ($s) {
                $qq->where('promoter_full_name', 'like', '%' . $s . '%')
                   ->orWhere('promoter_phone', 'like', '%' . $s . '%');
            });
        }

        // Фильтр по статусу (если у тебя есть в UI)
        if ($request->filled('status')) {
            $q->where('promoter_status', $request->input('status'));
        }

        $sort = $request->input('sort');
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        if ($sort === 'full_name') {
            $q->orderBy('promoter_full_name', $dir);
        } else {
            $q->orderBy('promoter_full_name');
        }

        $promoters = $q->paginate(30)->appends($request->query());

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

        return view('promoters.index', compact('promoters', 'cities', 'user'));
    }

    public function create(AccessService $accessService)
    {
        $this->assertPromoterWriteAccess($accessService);
        return view('promoters.create');
    }

    public function importForm(AccessService $accessService)
    {
        $this->assertPromoterWriteAccess($accessService);
        return view('promoters.import');
    }

    public function import(Request $request, AccessService $accessService)
    {
        $request->validate([
            'csv_file' => ['required', 'file'],
        ]);

        $this->assertPromoterWriteAccess($accessService);

        $branchId = null;
        if (Schema::hasColumn('promoters', 'branch_id') && auth()->check()) {
            $branchId = auth()->user()->branch_id;
        }

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

        if (!isset($map['promoter_full_name'])) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Не найдена колонка promoter_full_name']);
        }

        $columns = Schema::getColumnListing('promoters');
        $hasRequisites = Schema::hasColumn('promoters', 'promoter_requisites');
        $imported = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $row = array_map(fn($v) => trim((string) $v), $row);

            $fullName = $row[$map['promoter_full_name']] ?? '';
            if ($fullName === '') {
                continue;
            }

            $phone = $map['promoter_phone'] ?? null;
            $status = $map['promoter_status'] ?? null;
            $hiredAt = $map['hired_at'] ?? null;
            $firedAt = $map['fired_at'] ?? null;
            $comment = $map['promoter_comment'] ?? null;
            $requisites = $map['promoter_requisites'] ?? null;

            $payload = [
                'promoter_full_name' => $fullName,
                'promoter_phone' => $phone !== null ? $this->normalizePhone($row[$phone] ?? null) : null,
                'promoter_status' => $status !== null && ($row[$status] ?? '') !== ''
                    ? $row[$status]
                    : 'active',
                'hired_at' => $hiredAt !== null ? $this->normalizeDate($row[$hiredAt] ?? null) : null,
                'fired_at' => $firedAt !== null ? $this->normalizeDate($row[$firedAt] ?? null) : null,
                'promoter_comment' => $comment !== null ? ($row[$comment] ?? null) : null,
            ];

            if ($hasRequisites) {
                $payload['promoter_requisites'] = $requisites !== null ? ($row[$requisites] ?? null) : null;
            }

            $payload['promoter_status'] = $this->applyStatusRules(
                $payload['promoter_status'],
                $payload['hired_at'] ?? null,
                $payload['fired_at'] ?? null
            );

            if (!empty($branchId) && in_array('branch_id', $columns, true)) {
                $payload['branch_id'] = $branchId;
            }

            $payload = array_intersect_key($payload, array_flip($columns));

            if (!empty($payload['promoter_phone'])) {
                Promoter::updateOrCreate(
                    ['promoter_phone' => $payload['promoter_phone']],
                    $payload
                );
            } else {
                Promoter::updateOrCreate(
                    ['promoter_full_name' => $payload['promoter_full_name']],
                    $payload
                );
            }

            $imported++;
        }

        fclose($handle);

        return back()->with('ok', 'Импортировано: ' . $imported);
    }

    public function store(Request $request, AccessService $accessService)
    {
        $this->assertPromoterWriteAccess($accessService);
        $data = $this->validatePromoter($request);
        $columns = Schema::getColumnListing('promoters');
        $branchId = null;
        if (Schema::hasColumn('promoters', 'branch_id') && auth()->check()) {
            $branchId = auth()->user()->branch_id;
        }

        // Архитектура статуса: fired_at имеет приоритет
        $data['promoter_status'] = $this->applyStatusRules(
            $data['promoter_status'] ?? 'trainee',
            $data['hired_at'] ?? null,
            $data['fired_at'] ?? null
        );

        $payload = [
            'promoter_full_name' => $data['promoter_full_name'],
            'promoter_phone' => $data['promoter_phone'] ?? null,
            'promoter_status' => $data['promoter_status'],
            'hired_at' => $data['hired_at'] ?? null,
            'fired_at' => $data['fired_at'] ?? null,
            'promoter_comment' => $data['promoter_comment'] ?? null,
        ];

        if (Schema::hasColumn('promoters', 'promoter_requisites')) {
            $payload['promoter_requisites'] = $data['promoter_requisites'] ?? null;
        }

        if (!empty($branchId) && in_array('branch_id', $columns, true)) {
            $payload['branch_id'] = $branchId;
        }

        $payload = array_intersect_key($payload, array_flip($columns));
        Promoter::create($payload);

        return redirect()->route('module.promoters')->with('ok', 'Промоутер добавлен');
    }

    public function edit(Promoter $promoter, AccessService $accessService)
    {
        $this->assertPromoterAccess($accessService, $promoter);
        return view('promoters.edit', compact('promoter'));
    }

    public function update(Request $request, Promoter $promoter, AccessService $accessService)
    {
        $this->assertPromoterAccess($accessService, $promoter);
        $data = $this->validatePromoter($request);
        $columns = Schema::getColumnListing('promoters');

        // Архитектура статуса: fired_at имеет приоритет
        $data['promoter_status'] = $this->applyStatusRules(
            $data['promoter_status'] ?? $promoter->promoter_status,
            $data['hired_at'] ?? null,
            $data['fired_at'] ?? null
        );

        $payload = [
            'promoter_full_name' => $data['promoter_full_name'],
            'promoter_phone' => $data['promoter_phone'] ?? null,
            'promoter_status' => $data['promoter_status'],
            'hired_at' => $data['hired_at'] ?? null,
            'fired_at' => $data['fired_at'] ?? null,
            'promoter_comment' => $data['promoter_comment'] ?? null,
        ];

        if (Schema::hasColumn('promoters', 'promoter_requisites')) {
            $payload['promoter_requisites'] = $data['promoter_requisites'] ?? null;
        }

        $payload = array_intersect_key($payload, array_flip($columns));
        $promoter->update($payload);

        return redirect()->route('module.promoters')->with('ok', 'Промоутер обновлён');
    }

    public function destroy(Promoter $promoter, AccessService $accessService)
    {
        $this->assertPromoterAccess($accessService, $promoter);
        $promoter->delete();
        return redirect()->route('module.promoters')->with('ok', 'Промоутер удалён');
    }

    private function assertPromoterAccess(AccessService $accessService, Promoter $promoter): void
    {
        $user = auth()->user();
        if (!$user || !$accessService->canAccessPromoter($user, $promoter)) {
            abort(403, 'Нет доступа к промоутеру');
        }
    }

    private function assertPromoterWriteAccess(AccessService $accessService): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        // Проверяем права на редактирование модуля promoters
        if (!$accessService->canAccessModule($user, 'promoters', 'edit')) {
            abort(403, 'Нет прав на редактирование промоутеров');
        }

        if ($accessService->isFullAccess($user)) {
            return;
        }

        if (empty($user->branch_id)) {
            abort(403, 'Не указан филиал для записи');
        }
    }

    private function validatePromoter(Request $request): array
    {
        $rules = [
            'promoter_full_name' => ['required', 'string', 'max:255'],
            'promoter_phone' => ['nullable', 'string', 'max:50'],
            'promoter_status' => ['required', 'in:active,trainee,paused,fired'],
            'hired_at' => ['nullable', 'date'],
            'fired_at' => ['nullable', 'date'],
            'promoter_comment' => ['nullable', 'string', 'max:255'],
        ];

        if (Schema::hasColumn('promoters', 'promoter_requisites')) {
            $rules['promoter_requisites'] = ['nullable', 'string', 'max:255'];
        }

        return $request->validate($rules);
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '.' || $value === '-') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizePhone(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '.' || $value === '-') {
            return null;
        }

        return $value;
    }

    /**
     * Правило архитектуры:
     * - fired_at <= сегодня => статус всегда fired (и сохраняем его в БД)
     * - иначе — оставляем выбранный статус
     */
    private function applyStatusRules(string $selectedStatus, ?string $hiredAt, ?string $firedAt): string
    {
        if (!empty($firedAt)) {
            $fa = Carbon::parse($firedAt)->startOfDay();
            $today = Carbon::now()->startOfDay();

            if ($fa->lessThanOrEqualTo($today)) {
                return 'fired';
            }
        }

        return $selectedStatus;
    }
}
