<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InterviewsController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $q = Interview::query()->with(['createdBy']);
        $hasInterviewTime = Schema::hasColumn('interviews', 'interview_time');

        // Фильтрация по доступу
        $user = $request->user();
        if ($user) {
            $accessService = app(\App\Services\AccessService::class);
            // Если пользователь ограничен филиалом, показываем только собеседования созданные пользователями его филиала
            if ($accessService->isBranchScoped($user) && !empty($user->branch_id)) {
                $q->whereHas('createdBy', function ($query) use ($user) {
                    $query->where('branch_id', $user->branch_id);
                });
            } elseif ($accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $q->whereHas('createdBy', function ($query) use ($region) {
                        $query->whereHas('city', function ($q) use ($region) {
                            $q->where('region_name', $region);
                        })->orWhereHas('branch.city', function ($q) use ($region) {
                            $q->where('region_name', $region);
                        });
                    });
                } else {
                    $q->whereRaw('1=0');
                }
            }
        }

        if ($request->filled('date_from')) {
            $q->whereDate('interview_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('interview_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $q->where(function ($qq) use ($s) {
                $qq->where('candidate_name', 'like', '%' . $s . '%')
                   ->orWhere('candidate_phone', 'like', '%' . $s . '%')
                   ->orWhere('source', 'like', '%' . $s . '%');
            });
        }

        $interviewsQuery = $q
            ->orderByRaw(
                "CASE\n" .
                "WHEN status = 'planned' AND interview_date = ? THEN 0\n" .
                "WHEN status = 'planned' THEN 1\n" .
                "ELSE 2\n" .
                "END ASC",
                [$today]
            );

        if ($hasInterviewTime) {
            $interviewsQuery->orderByRaw(
                "CASE\n" .
                "WHEN status = 'planned' AND interview_time IS NULL THEN 1\n" .
                "WHEN status = 'planned' THEN 0\n" .
                "ELSE NULL\n" .
                "END ASC"
            );
        }

        $interviewsQuery
            ->orderByRaw("CASE WHEN status = 'planned' THEN interview_date END ASC");

        if ($hasInterviewTime) {
            $interviewsQuery->orderByRaw("CASE WHEN status = 'planned' THEN interview_time END ASC");
        }

        $interviewsQuery
            ->orderByRaw("CASE WHEN status <> 'planned' THEN interview_date END DESC");

        if ($hasInterviewTime) {
            $interviewsQuery->orderByRaw("CASE WHEN status <> 'planned' THEN interview_time END DESC");
        }

        $interviews = $interviewsQuery
            ->orderByDesc('interview_id')
            ->paginate(30)
            ->appends($request->query());

        return view('interviews.index', compact('interviews'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $accessService = app(\App\Services\AccessService::class);
        if (!$accessService->canAccessModule($user, 'interviews', 'edit')) {
            abort(403, 'Нет прав на создание собеседований');
        }

        return view('interviews.create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $accessService = app(\App\Services\AccessService::class);
        if (!$accessService->canAccessModule($user, 'interviews', 'edit')) {
            abort(403, 'Нет прав на создание собеседований');
        }

        $data = $this->validateInterview($request);
        $hasInterviewTime = Schema::hasColumn('interviews', 'interview_time');

        $payload = [
            'interview_date' => $data['interview_date'],
            'candidate_name' => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? 'planned',
            'comment' => $data['comment'] ?? null,
            'created_by' => auth()->id(),
        ];

        if ($hasInterviewTime) {
            $payload['interview_time'] = $data['interview_time'] ?? null;
        }

        Interview::create($payload);

        return redirect()->route('interviews.index')->with('ok', 'Собеседование добавлено');
    }

    public function edit(Interview $interview)
    {
        return view('interviews.edit', compact('interview'));
    }

    public function update(Request $request, Interview $interview)
    {
        $data = $this->validateInterview($request);
        $hasInterviewTime = Schema::hasColumn('interviews', 'interview_time');

        $payload = [
            'interview_date' => $data['interview_date'],
            'candidate_name' => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? 'planned',
            'comment' => $data['comment'] ?? null,
        ];

        if ($hasInterviewTime) {
            $payload['interview_time'] = $data['interview_time'] ?? null;
        }

        $interview->update($payload);

        return redirect()->route('interviews.index')->with('ok', 'Собеседование обновлено');
    }

    public function destroy(Interview $interview)
    {
        $interview->delete();
        return redirect()->route('interviews.index')->with('ok', 'Собеседование удалено');
    }

    private function validateInterview(Request $request): array
    {
        return $request->validate([
            'interview_date' => ['required', 'date'],
            'interview_time' => ['nullable', 'date_format:H:i'],
            'candidate_name' => ['required', 'string', 'max:255'],
            'candidate_phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:planned,came,no_show,hired,rejected'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
