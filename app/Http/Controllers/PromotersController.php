<?php

namespace App\Http\Controllers;

use App\Models\Promoter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PromotersController extends Controller
{
    public function index(Request $request)
    {
        $q = Promoter::query();

        // Поиск (ФИО + телефон)
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $q->where(function ($qq) use ($s) {
                $qq->where('promoter_full_name', 'like', '%' . $s . '%')
                   ->orWhere('promoter_phone', 'like', '%' . $s . '%');
            });
        }

        // Фильтр по статусу (если у тебя есть в UI)
        if ($request->filled('status')) {
            $q->where('promoter_status', $request->input('status'));
        }

        $promoters = $q->orderBy('promoter_full_name')
            ->paginate(30)
            ->appends($request->query());

        return view('promoters.index', compact('promoters'));
    }

    public function create()
    {
        return view('promoters.create');
    }

    public function importForm()
    {
        return view('promoters.import');
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

    public function store(Request $request)
    {
        $data = $this->validatePromoter($request);
        $columns = Schema::getColumnListing('promoters');

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

        $payload = array_intersect_key($payload, array_flip($columns));
        Promoter::create($payload);

        return redirect()->route('module.promoters')->with('ok', 'Промоутер добавлен');
    }

    public function edit(Promoter $promoter)
    {
        return view('promoters.edit', compact('promoter'));
    }

    public function update(Request $request, Promoter $promoter)
    {
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

    public function destroy(Promoter $promoter)
    {
        $promoter->delete();
        return redirect()->route('module.promoters')->with('ok', 'Промоутер удалён');
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
