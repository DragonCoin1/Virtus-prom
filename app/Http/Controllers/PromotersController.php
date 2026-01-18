<?php

namespace App\Http\Controllers;

use App\Models\Promoter;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $data = $this->validatePromoter($request);

        // Архитектура статуса: fired_at имеет приоритет
        $data['promoter_status'] = $this->applyStatusRules(
            $data['promoter_status'] ?? 'trainee',
            $data['hired_at'] ?? null,
            $data['fired_at'] ?? null
        );

        Promoter::create([
            'promoter_full_name' => $data['promoter_full_name'],
            'promoter_phone' => $data['promoter_phone'] ?? null,
            'promoter_requisites' => $data['promoter_requisites'] ?? null,
            'promoter_status' => $data['promoter_status'],
            'hired_at' => $data['hired_at'] ?? null,
            'fired_at' => $data['fired_at'] ?? null,
            'promoter_comment' => $data['promoter_comment'] ?? null,
        ]);

        return redirect()->route('module.promoters')->with('ok', 'Промоутер добавлен');
    }

    public function edit(Promoter $promoter)
    {
        return view('promoters.edit', compact('promoter'));
    }

    public function update(Request $request, Promoter $promoter)
    {
        $data = $this->validatePromoter($request);

        // Архитектура статуса: fired_at имеет приоритет
        $data['promoter_status'] = $this->applyStatusRules(
            $data['promoter_status'] ?? $promoter->promoter_status,
            $data['hired_at'] ?? null,
            $data['fired_at'] ?? null
        );

        $promoter->update([
            'promoter_full_name' => $data['promoter_full_name'],
            'promoter_phone' => $data['promoter_phone'] ?? null,
            'promoter_requisites' => $data['promoter_requisites'] ?? null,
            'promoter_status' => $data['promoter_status'],
            'hired_at' => $data['hired_at'] ?? null,
            'fired_at' => $data['fired_at'] ?? null,
            'promoter_comment' => $data['promoter_comment'] ?? null,
        ]);

        return redirect()->route('module.promoters')->with('ok', 'Промоутер обновлён');
    }

    public function destroy(Promoter $promoter)
    {
        $promoter->delete();
        return redirect()->route('module.promoters')->with('ok', 'Промоутер удалён');
    }

    private function validatePromoter(Request $request): array
    {
        return $request->validate([
            'promoter_full_name' => ['required', 'string', 'max:255'],
            'promoter_phone' => ['nullable', 'string', 'max:50'],
            'promoter_requisites' => ['nullable', 'string', 'max:255'],
            'promoter_status' => ['required', 'in:active,trainee,paused,fired'],
            'hired_at' => ['nullable', 'date'],
            'fired_at' => ['nullable', 'date'],
            'promoter_comment' => ['nullable', 'string', 'max:255'],
        ]);
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
