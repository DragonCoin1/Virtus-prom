<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Services\AccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InterviewsController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $today = Carbon::today()->toDateString();
        $q = Interview::query()->with(['createdBy']);
        $hasInterviewTime = Schema::hasColumn('interviews', 'interview_time');

        // Фильтрация по доступу
        $user = $request->user();
        if ($user) {
            $accessService = app(\App\Services\AccessService::class);
            $hasCityId = \Illuminate\Support\Facades\Schema::hasColumn('interviews', 'city_id');
            
            // Фильтр по городу из запроса (для developer, general_director, regional_director)
            $filterCityId = $request->input('city_id');
            
            if ($hasCityId) {
                // Фильтрация через city_id
                if ($accessService->isManager($user) && !empty($user->branch_id)) {
                    // Менеджер - только свой город через branch_id
                    $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
                    if ($cityId) {
                        $q->where('city_id', $cityId);
                    } else {
                        $q->whereRaw('1=0');
                    }
                } elseif ($accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user)) {
                    // Региональный директор и директор - города из списка
                    $cityIds = $accessService->getDirectorCityIds($user);
                    if (!empty($cityIds)) {
                        if ($filterCityId) {
                            // Если выбран конкретный город - проверяем доступ
                            if (in_array($filterCityId, $cityIds)) {
                                $q->where('city_id', $filterCityId);
                            }
                        } else {
                            $q->whereIn('city_id', $cityIds);
                        }
                    } else {
                        $q->whereRaw('1=0');
                    }
                } elseif (($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) && $filterCityId) {
                    // Developer и General Director - фильтр по выбранному городу
                    $q->where('city_id', $filterCityId);
                }
            } else {
                // Если столбца еще нет, фильтруем через createdBy
                if ($accessService->isBranchScoped($user) && !empty($user->branch_id)) {
                    $q->whereHas('createdBy', function ($query) use ($user) {
                        $query->where('branch_id', $user->branch_id);
                    });
                } elseif ($accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user)) {
                    $cityIds = $accessService->getDirectorCityIds($user);
                    if (!empty($cityIds)) {
                        $q->whereHas('createdBy', function ($query) use ($cityIds) {
                            $query->where(function ($q) use ($cityIds) {
                                $q->whereIn('city_id', $cityIds)
                                  ->orWhereHas('branch', function ($branchQuery) use ($cityIds) {
                                      $branchQuery->whereIn('city_id', $cityIds);
                                  });
                            });
                        });
                    } else {
                        $q->whereRaw('1=0');
                    }
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

        // Получаем доступные города для фильтра
        $cities = collect();
        if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user))) {
            if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
                $cities = \App\Models\City::orderBy('city_name')->get();
            } elseif ($accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user)) {
                $cityIds = $accessService->getDirectorCityIds($user);
                if (!empty($cityIds)) {
                    $cities = \App\Models\City::whereIn('city_id', $cityIds)->orderBy('city_name')->get();
                }
            }
        }

        return view('interviews.index', compact('interviews', 'cities', 'user'));
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

        // Для директора и менеджера - город определяется автоматически, не показываем выбор
        $cities = null;
        $showCitySelect = false;
        
        if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
            // Developer и General Director - все города
            $cities = \App\Models\City::orderBy('city_name')->get();
            $showCitySelect = true;
        } elseif ($accessService->isRegionalDirector($user)) {
            // Региональный директор - города из списка
                $cityIds = $accessService->getDirectorCityIds($user);
            if (!empty($cityIds)) {
                $cities = \App\Models\City::whereIn('city_id', $cityIds)->orderBy('city_name')->get();
                $showCitySelect = true;
            }
        }
        // Для директора и менеджера - city_id будет установлен автоматически, не показываем выбор

        return view('interviews.create', compact('cities', 'user', 'showCitySelect'));
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

        $data = $this->validateInterview($request, $user, $accessService);
        $hasInterviewTime = Schema::hasColumn('interviews', 'interview_time');

        // Определяем city_id
        $cityId = null;
        
        // Для директора и менеджера - автоматически из филиала (приоритет, игнорируем форму)
        if (($accessService->isManager($user) || $accessService->isBranchDirector($user)) && !empty($user->branch_id)) {
            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
        } elseif (!empty($data['city_id'])) {
            // Для остальных - из формы, если указан
            $cityId = (int) $data['city_id'];
            // Проверяем доступ к городу
            if (!$this->canAccessCity($user, $accessService, $cityId)) {
                abort(403, 'Нет доступа к выбранному городу');
            }
        }

        $payload = [
            'interview_date' => $data['interview_date'],
            'candidate_name' => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? 'planned',
            'comment' => $data['comment'] ?? null,
            'created_by' => auth()->id(),
        ];

        if ($cityId) {
            $payload['city_id'] = $cityId;
        }

        if ($hasInterviewTime) {
            $payload['interview_time'] = $data['interview_time'] ?? null;
        }

        Interview::create($payload);

        return redirect()->route('interviews.index')->with('ok', 'Собеседование добавлено');
    }

    public function edit(Interview $interview)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $accessService = app(\App\Services\AccessService::class);
        
        // Проверяем доступ к собеседованию через город
        if ($interview->city_id && !$this->canAccessCity($user, $accessService, $interview->city_id)) {
            abort(403, 'Нет доступа к этому собеседованию');
        }

        // Для директора и менеджера - город определяется автоматически, не показываем выбор
        $cities = null;
        $showCitySelect = false;
        
        if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
            // Developer и General Director - все города
            $cities = \App\Models\City::orderBy('city_name')->get();
            $showCitySelect = true;
        } elseif ($accessService->isRegionalDirector($user)) {
            // Региональный директор - города из списка
                $cityIds = $accessService->getDirectorCityIds($user);
            if (!empty($cityIds)) {
                $cities = \App\Models\City::whereIn('city_id', $cityIds)->orderBy('city_name')->get();
                $showCitySelect = true;
            }
        }
        // Для директора и менеджера - city_id будет установлен автоматически, не показываем выбор

        return view('interviews.edit', compact('interview', 'cities', 'user', 'showCitySelect'));
    }

    public function update(Request $request, Interview $interview)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $accessService = app(\App\Services\AccessService::class);
        
        // Проверяем доступ к собеседованию через город
        if ($interview->city_id && !$this->canAccessCity($user, $accessService, $interview->city_id)) {
            abort(403, 'Нет доступа к этому собеседованию');
        }

        $data = $this->validateInterview($request, $user, $accessService);
        $hasInterviewTime = Schema::hasColumn('interviews', 'interview_time');

        // Определяем city_id
        $cityId = null;
        
        // Для директора и менеджера - автоматически из филиала (приоритет, нельзя менять)
        if (($accessService->isManager($user) || $accessService->isBranchDirector($user)) && !empty($user->branch_id)) {
            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
        } elseif (!empty($data['city_id'])) {
            // Для остальных - из формы, если указан
            $cityId = (int) $data['city_id'];
            // Проверяем доступ к городу
            if (!$this->canAccessCity($user, $accessService, $cityId)) {
                abort(403, 'Нет доступа к выбранному городу');
            }
        }

        $payload = [
            'interview_date' => $data['interview_date'],
            'candidate_name' => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? 'planned',
            'comment' => $data['comment'] ?? null,
        ];

        if ($cityId) {
            $payload['city_id'] = $cityId;
        }

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

    private function validateInterview(Request $request, $user = null, $accessService = null): array
    {
        $rules = [
            'interview_date' => ['required', 'date'],
            'interview_time' => ['nullable', 'date_format:H:i'],
            'candidate_name' => ['required', 'string', 'max:255'],
            'candidate_phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:planned,came,no_show,hired,rejected'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];

        // Для developer и general_director city_id опционально (могут выбрать любой)
        // Для остальных - опционально, но если указан - проверяем доступ
        if ($user && $accessService) {
            $rules['city_id'] = ['nullable', 'integer', 'exists:cities,city_id'];
        }

        return $request->validate($rules);
    }

    private function getAccessibleCities($user, $accessService)
    {
        if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
            // Все города
            return \App\Models\City::orderBy('city_name')->get();
        } elseif ($accessService->isRegionalDirector($user)) {
            // Города из списка
                $cityIds = $accessService->getDirectorCityIds($user);
            if (!empty($cityIds)) {
                return \App\Models\City::whereIn('city_id', $cityIds)->orderBy('city_name')->get();
            }
            return collect();
        } elseif (($accessService->isBranchDirector($user) || $accessService->isManager($user)) && !empty($user->branch_id)) {
            // Город своего филиала
            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
            if ($cityId) {
                return \App\Models\City::where('city_id', $cityId)->get();
            }
            return collect();
        }

        return collect();
    }

    private function canAccessCity($user, $accessService, int $cityId): bool
    {
        if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
            return true;
        } elseif ($accessService->isRegionalDirector($user)) {
                $cityIds = $accessService->getDirectorCityIds($user);
            return in_array($cityId, $cityIds);
        } elseif (($accessService->isBranchDirector($user) || $accessService->isManager($user)) && !empty($user->branch_id)) {
            $userCityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
            return (int) $userCityId === $cityId;
        }

        return false;
    }
}
