<?php

namespace App\Http\Controllers;

use App\Models\Promoter;
use App\Models\RouteAction;
use App\Models\SalaryAdjustment;
use App\Services\AccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $promotersQuery = Promoter::orderBy('promoter_full_name');
        $user = $request->user();
        if ($user) {
            $accessService->scopePromoters($promotersQuery, $user);
        }
        $promoters = $promotersQuery->get();
        $promoterIds = $promoters->pluck('promoter_id')->map(fn ($id) => (int) $id)->all();

        // Фильтр по городу (для developer, general_director, regional_director)
        $cityId = $request->input('city_id');
        if ($cityId && $user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            // Фильтруем промоутеров по городу
            $promoters = $promoters->filter(function ($promoter) use ($cityId) {
                return $promoter->branch && $promoter->branch->city_id == $cityId;
            });
            $promoterIds = $promoters->pluck('promoter_id')->map(fn ($id) => (int) $id)->all();
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $promoterId = $request->input('promoter_id');

        $actionsQ = RouteAction::query();

        if (empty($promoterIds)) {
            $actionsQ->whereRaw('1=0');
        } else {
            $actionsQ->whereIn('promoter_id', $promoterIds);
        }

        if (!empty($dateFrom)) {
            $actionsQ->whereDate('action_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $actionsQ->whereDate('action_date', '<=', $dateTo);
        }
        if (!empty($promoterId)) {
            if ($user && !$accessService->canAccessPromoterId($user, (int) $promoterId)) {
                abort(403, 'Нет доступа к промоутеру');
            }
            $actionsQ->where('promoter_id', (int)$promoterId);
        }

        $actionsAgg = $actionsQ->select([
                'promoter_id',
                DB::raw('SUM(payment_amount) as sum_payment'),
            ])
            ->groupBy('promoter_id')
            ->get()
            ->keyBy('promoter_id');

        $adjQ = SalaryAdjustment::query();

        // Фильтрация корректировок по городу для менеджеров
        // Проверяем наличие столбца city_id перед фильтрацией
        $hasCityId = \Illuminate\Support\Facades\Schema::hasColumn('salary_adjustments', 'city_id');
        
        if ($hasCityId) {
            if ($user && $accessService->isManager($user) && !empty($user->branch_id)) {
                $userCityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
                if ($userCityId) {
                    $adjQ->where('city_id', $userCityId);
                } else {
                    $adjQ->whereRaw('1=0');
                }
            } elseif ($user && $accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $adjQ->whereHas('city', function ($query) use ($region) {
                        $query->where('region_name', $region);
                    });
                } else {
                    $adjQ->whereRaw('1=0');
                }
            }
        } else {
            // Если столбца нет, фильтруем через промоутера
            if ($user && $accessService->isManager($user) && !empty($user->branch_id)) {
                $adjQ->whereHas('promoter', function ($query) use ($user) {
                    $query->where('branch_id', $user->branch_id);
                });
            } elseif ($user && $accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $adjQ->whereHas('promoter.branch.city', function ($query) use ($region) {
                        $query->where('region_name', $region);
                    });
                } else {
                    $adjQ->whereRaw('1=0');
                }
            }
        }

        if (empty($promoterIds)) {
            $adjQ->whereRaw('1=0');
        } else {
            $adjQ->whereIn('promoter_id', $promoterIds);
        }

        if (!empty($dateFrom)) {
            $adjQ->whereDate('adj_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $adjQ->whereDate('adj_date', '<=', $dateTo);
        }
        if (!empty($promoterId)) {
            if ($user && !$accessService->canAccessPromoterId($user, (int) $promoterId)) {
                abort(403, 'Нет доступа к промоутеру');
            }
            $adjQ->where('promoter_id', (int)$promoterId);
        }

        $adjAgg = $adjQ->select([
                'promoter_id',
                DB::raw('SUM(amount) as sum_adj'),
            ])
            ->groupBy('promoter_id')
            ->get()
            ->keyBy('promoter_id');

        $ids = array_unique(array_merge(
            $actionsAgg->keys()->toArray(),
            $adjAgg->keys()->toArray()
        ));
        sort($ids);

        $rows = [];
        $totalPayment = 0;
        $totalAdj = 0;
        $totalFinal = 0;

        foreach ($ids as $pid) {
            $p = $promoters->firstWhere('promoter_id', $pid);

            $sumPay = (int)($actionsAgg[$pid]->sum_payment ?? 0);
            $sumAdj = (int)($adjAgg[$pid]->sum_adj ?? 0);
            $sumFinal = $sumPay + $sumAdj;

            $rows[] = [
                'promoter_id' => $pid,
                'promoter_name' => $p ? $p->promoter_full_name : ('ID ' . $pid),
                'sum_payment' => $sumPay,
                'sum_adj' => $sumAdj,
                'sum_final' => $sumFinal,
            ];

            $totalPayment += $sumPay;
            $totalAdj += $sumAdj;
            $totalFinal += $sumFinal;
        }

        $canEdit = false;
        if (auth()->check()) {
            $canEdit = (int)DB::table('role_module_access')
                ->where('role_id', auth()->user()->role_id)
                ->where('module_code', 'salary')
                ->value('can_edit') === 1;
        }

        // Фильтрация корректировок по городу для менеджеров
        $hasCityId = \Illuminate\Support\Facades\Schema::hasColumn('salary_adjustments', 'city_id');
        $lastAdjustmentsQuery = SalaryAdjustment::with(['promoter', 'createdBy']);
        if ($hasCityId) {
            $lastAdjustmentsQuery->with('city');
        }
        
        if ($hasCityId) {
            if ($user && $accessService->isManager($user) && !empty($user->branch_id)) {
                // Менеджер видит только корректировки своего города
                $userCityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
                if ($userCityId) {
                    $lastAdjustmentsQuery->where('city_id', $userCityId);
                } else {
                    $lastAdjustmentsQuery->whereRaw('1=0');
                }
            } elseif ($user && $accessService->isRegionalDirector($user)) {
                // Региональный директор видит корректировки своего региона
                $region = $accessService->regionName($user);
                if ($region) {
                    $lastAdjustmentsQuery->whereHas('city', function ($query) use ($region) {
                        $query->where('region_name', $region);
                    });
                } else {
                    $lastAdjustmentsQuery->whereRaw('1=0');
                }
            } elseif ($user && $accessService->isBranchScoped($user) && !empty($user->branch_id)) {
                // Остальные с ограничением по филиалу - через промоутера
                $lastAdjustmentsQuery->whereHas('promoter', function ($query) use ($user) {
                    $query->where('branch_id', $user->branch_id);
                });
            }
        } else {
            // Если столбца нет, фильтруем через промоутера
            if ($user && $accessService->isManager($user) && !empty($user->branch_id)) {
                $lastAdjustmentsQuery->whereHas('promoter', function ($query) use ($user) {
                    $query->where('branch_id', $user->branch_id);
                });
            } elseif ($user && $accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $lastAdjustmentsQuery->whereHas('promoter.branch.city', function ($query) use ($region) {
                        $query->where('region_name', $region);
                    });
                } else {
                    $lastAdjustmentsQuery->whereRaw('1=0');
                }
            } elseif ($user && $accessService->isBranchScoped($user) && !empty($user->branch_id)) {
                $lastAdjustmentsQuery->whereHas('promoter', function ($query) use ($user) {
                    $query->where('branch_id', $user->branch_id);
                });
            }
        }
        
        $lastAdjustments = $lastAdjustmentsQuery
            ->orderByDesc('adj_date')
            ->orderByDesc('salary_adjustment_id')
            ->limit(50)
            ->get();

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

        return view('salary.index', compact(
            'promoters',
            'rows',
            'totalPayment',
            'totalAdj',
            'totalFinal',
            'canEdit',
            'lastAdjustments',
            'cities',
            'user'
        ));
    }

    public function createAdjustment(AccessService $accessService)
    {
        $this->assertSalaryEditAccess($accessService);
        $promotersQuery = Promoter::orderBy('promoter_full_name');
        $user = auth()->user();
        if ($user) {
            $accessService->scopePromoters($promotersQuery, $user);
        }
        $promoters = $promotersQuery->get();
        
        // Для девелопера показываем все города для выбора
        $cities = collect();
        if ($user && $accessService->isDeveloper($user)) {
            $cities = \App\Models\City::orderBy('city_name')->get();
        }
        
        return view('salary.adjustment_create', compact('promoters', 'cities', 'user'));
    }

    public function storeAdjustment(Request $request, AccessService $accessService)
    {
        $this->assertSalaryEditAccess($accessService);
        $user = $request->user();
        
        $validationRules = [
            'promoter_id' => ['required', 'integer', 'exists:promoters,promoter_id'],
            'adj_date' => ['required', 'date'],
            'amount' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
        
        // Для девелопера city_id обязателен
        if ($user && $accessService->isDeveloper($user)) {
            $validationRules['city_id'] = ['required', 'integer', 'exists:cities,city_id'];
        }
        
        $data = $request->validate($validationRules);

        if ($user && !$accessService->canAccessPromoterId($user, (int) $data['promoter_id'])) {
            abort(403, 'Нет доступа к промоутеру');
        }

        // Определяем city_id
        $cityId = null;
        if ($user && $accessService->isDeveloper($user)) {
            // Девелопер указывает город явно
            $cityId = (int) $data['city_id'];
        } else {
            // Для остальных - берем город из филиала промоутера
            $promoter = Promoter::find((int) $data['promoter_id']);
            if ($promoter && $promoter->branch_id) {
                $cityId = \App\Models\Branch::where('branch_id', $promoter->branch_id)->value('city_id');
            }
        }

        SalaryAdjustment::create([
            'promoter_id' => (int)$data['promoter_id'],
            'city_id' => $cityId,
            'adj_date' => $data['adj_date'],
            'amount' => (int)$data['amount'],
            'comment' => $data['comment'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('salary.index')->with('ok', 'Корректировка добавлена');
    }

    public function editAdjustment(SalaryAdjustment $salaryAdjustment, AccessService $accessService)
    {
        $this->assertSalaryEditAccess($accessService);
        $salaryAdjustment->load('promoter', 'city');
        $user = auth()->user();
        if ($user && !$accessService->canAccessPromoter($user, $salaryAdjustment->promoter)) {
            abort(403, 'Нет доступа к промоутеру');
        }

        $promotersQuery = Promoter::orderBy('promoter_full_name');
        if ($user) {
            $accessService->scopePromoters($promotersQuery, $user);
        }
        $promoters = $promotersQuery->get();
        
        // Для девелопера показываем все города для выбора
        $cities = collect();
        if ($user && $accessService->isDeveloper($user)) {
            $cities = \App\Models\City::orderBy('city_name')->get();
        }

        return view('salary.adjustment_edit', compact('promoters', 'salaryAdjustment', 'cities', 'user'));
    }

    public function updateAdjustment(Request $request, SalaryAdjustment $salaryAdjustment, AccessService $accessService)
    {
        $this->assertSalaryEditAccess($accessService);
        $user = $request->user();
        
        $validationRules = [
            'promoter_id' => ['required', 'integer', 'exists:promoters,promoter_id'],
            'adj_date' => ['required', 'date'],
            'amount' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
        
        // Для девелопера city_id обязателен
        if ($user && $accessService->isDeveloper($user)) {
            $validationRules['city_id'] = ['required', 'integer', 'exists:cities,city_id'];
        }
        
        $data = $request->validate($validationRules);

        if ($user && !$accessService->canAccessPromoterId($user, (int) $data['promoter_id'])) {
            abort(403, 'Нет доступа к промоутеру');
        }

        // Определяем city_id
        $cityId = null;
        if ($user && $accessService->isDeveloper($user)) {
            // Девелопер указывает город явно
            $cityId = (int) $data['city_id'];
        } else {
            // Для остальных - берем город из филиала промоутера
            $promoter = Promoter::find((int) $data['promoter_id']);
            if ($promoter && $promoter->branch_id) {
                $cityId = \App\Models\Branch::where('branch_id', $promoter->branch_id)->value('city_id');
            }
        }

        $salaryAdjustment->update([
            'promoter_id' => (int)$data['promoter_id'],
            'city_id' => $cityId,
            'adj_date' => $data['adj_date'],
            'amount' => (int)$data['amount'],
            'comment' => $data['comment'] ?? null,
        ]);

        return redirect()->route('salary.index')->with('ok', 'Корректировка обновлена');
    }

    public function destroyAdjustment(SalaryAdjustment $salaryAdjustment, AccessService $accessService)
    {
        $this->assertSalaryEditAccess($accessService);
        $salaryAdjustment->load('promoter');
        $user = auth()->user();
        if ($user && !$accessService->canAccessPromoter($user, $salaryAdjustment->promoter)) {
            abort(403, 'Нет доступа к промоутеру');
        }

        $salaryAdjustment->delete();
        return redirect()->route('salary.index')->with('ok', 'Корректировка удалена');
    }

    private function assertSalaryEditAccess(AccessService $accessService): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        if (!$accessService->canEditSalary($user)) {
            abort(403, 'Менеджеру запрещено редактировать зарплату');
        }
    }
}
