<?php

namespace App\Http\Controllers;

use App\Models\Promoter;
use App\Models\RouteAction;
use App\Models\SalaryAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $promoters = Promoter::orderBy('promoter_full_name')->get();

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $promoterId = $request->input('promoter_id');

        $actionsQ = RouteAction::query();

        if (!empty($dateFrom)) {
            $actionsQ->whereDate('action_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $actionsQ->whereDate('action_date', '<=', $dateTo);
        }
        if (!empty($promoterId)) {
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

        if (!empty($dateFrom)) {
            $adjQ->whereDate('adj_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $adjQ->whereDate('adj_date', '<=', $dateTo);
        }
        if (!empty($promoterId)) {
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

        $lastAdjustments = SalaryAdjustment::with(['promoter', 'createdBy'])
            ->orderByDesc('adj_date')
            ->orderByDesc('salary_adjustment_id')
            ->limit(50)
            ->get();

        return view('salary.index', compact(
            'promoters',
            'rows',
            'totalPayment',
            'totalAdj',
            'totalFinal',
            'canEdit',
            'lastAdjustments'
        ));
    }

    public function createAdjustment()
    {
        $promoters = Promoter::orderBy('promoter_full_name')->get();
        return view('salary.adjustment_create', compact('promoters'));
    }

    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'promoter_id' => ['required', 'integer', 'exists:promoters,promoter_id'],
            'adj_date' => ['required', 'date'],
            'amount' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        SalaryAdjustment::create([
            'promoter_id' => (int)$data['promoter_id'],
            'adj_date' => $data['adj_date'],
            'amount' => (int)$data['amount'],
            'comment' => $data['comment'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('salary.index')->with('ok', 'Корректировка добавлена');
    }

    public function editAdjustment(SalaryAdjustment $salaryAdjustment)
    {
        $promoters = Promoter::orderBy('promoter_full_name')->get();

        return view('salary.adjustment_edit', compact('promoters', 'salaryAdjustment'));
    }

    public function updateAdjustment(Request $request, SalaryAdjustment $salaryAdjustment)
    {
        $data = $request->validate([
            'promoter_id' => ['required', 'integer', 'exists:promoters,promoter_id'],
            'adj_date' => ['required', 'date'],
            'amount' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $salaryAdjustment->update([
            'promoter_id' => (int)$data['promoter_id'],
            'adj_date' => $data['adj_date'],
            'amount' => (int)$data['amount'],
            'comment' => $data['comment'] ?? null,
        ]);

        return redirect()->route('salary.index')->with('ok', 'Корректировка обновлена');
    }

    public function destroyAdjustment(SalaryAdjustment $salaryAdjustment)
    {
        $salaryAdjustment->delete();
        return redirect()->route('salary.index')->with('ok', 'Корректировка удалена');
    }
}
