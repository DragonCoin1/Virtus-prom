<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        // Фильтр по умолчанию: текущий месяц по сегодня
        $defaultFrom = $now->copy()->startOfMonth()->format('Y-m-d');
        $defaultTo = $now->format('Y-m-d');

        $dateFrom = $request->input('date_from', $defaultFrom);
        $dateTo = $request->input('date_to', $defaultTo);

        $sort = $request->input('sort', 'desc'); // asc/desc
        $sort = ($sort === 'asc') ? 'asc' : 'desc';

        $dailyQ = DB::table('route_actions')
            ->whereDate('action_date', '>=', $dateFrom)
            ->whereDate('action_date', '<=', $dateTo)
            ->groupBy('action_date')
            ->select([
                'action_date',

                DB::raw('SUM(leaflets_total) as sum_leaflets'),
                DB::raw('SUM(boxes_done) as sum_boxes'),
                DB::raw('SUM(posters_total) as sum_posters'),
                DB::raw('SUM(cards_count) as sum_cards'),
                DB::raw('SUM(payment_amount) as sum_payment'),

                DB::raw('SUM(leaflets_issued) as sum_leaflets_issued'),
                DB::raw('SUM(posters_issued) as sum_posters_issued'),
                DB::raw('SUM(cards_issued) as sum_cards_issued'),
            ]);

        $daily = $dailyQ->orderBy('action_date', $sort)
            ->paginate(30)
            ->appends($request->query());

        $promoterPayments = collect();
        $dailyDates = $daily->pluck('action_date')->all();
        if (!empty($dailyDates)) {
            $hasRequisites = Schema::hasColumn('promoters', 'promoter_requisites');
            $groupBy = [
                'action_date',
                'promoters.promoter_id',
                'promoters.promoter_full_name',
            ];
            $select = [
                'action_date',
                'promoters.promoter_full_name',
                DB::raw('SUM(payment_amount) as sum_payment'),
            ];

            if ($hasRequisites) {
                $groupBy[] = 'promoters.promoter_requisites';
                $select[] = 'promoters.promoter_requisites';
            } else {
                $select[] = DB::raw('NULL as promoter_requisites');
            }

            $promoterPayments = DB::table('route_actions')
                ->join('promoters', 'promoters.promoter_id', '=', 'route_actions.promoter_id')
                ->whereDate('action_date', '>=', $dateFrom)
                ->whereDate('action_date', '<=', $dateTo)
                ->whereIn('action_date', $dailyDates)
                ->groupBy($groupBy)
                ->select($select)
                ->orderBy('action_date', $sort)
                ->orderBy('promoters.promoter_full_name')
                ->get();
        }

        // Верхняя сводка за текущий месяц (самоформируется)
        $monthFrom = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthTo = $now->copy()->endOfMonth()->format('Y-m-d');

        $m = DB::table('route_actions')
            ->whereDate('action_date', '>=', $monthFrom)
            ->whereDate('action_date', '<=', $monthTo)
            ->select([
                DB::raw('SUM(leaflets_total) as sum_leaflets'),
                DB::raw('SUM(boxes_done) as sum_boxes'),
                DB::raw('SUM(posters_total) as sum_posters'),
                DB::raw('SUM(cards_count) as sum_cards'),
                DB::raw('SUM(payment_amount) as sum_payment'),

                DB::raw('SUM(leaflets_issued) as sum_leaflets_issued'),
                DB::raw('SUM(posters_issued) as sum_posters_issued'),
                DB::raw('SUM(cards_issued) as sum_cards_issued'),
            ])
            ->first();

        $month = [
            'from' => $monthFrom,
            'to' => $monthTo,

            'leaflets' => (int)($m->sum_leaflets ?? 0),
            'boxes' => (int)($m->sum_boxes ?? 0),
            'posters' => (int)($m->sum_posters ?? 0),
            'cards' => (int)($m->sum_cards ?? 0),
            'payment' => (int)($m->sum_payment ?? 0),

            'leaflets_issued' => (int)($m->sum_leaflets_issued ?? 0),
            'posters_issued' => (int)($m->sum_posters_issued ?? 0),
            'cards_issued' => (int)($m->sum_cards_issued ?? 0),
        ];

        $priceBox = null;
        if ($month['boxes'] > 0) {
            $priceBox = round($month['payment'] / $month['boxes'], 2);
        }

        $priceLeaflet = null;
        if ($month['leaflets'] > 0) {
            $priceLeaflet = round($month['payment'] / $month['leaflets'], 2);
        }

        return view('reports.index', compact(
            'daily',
            'promoterPayments',
            'dateFrom',
            'dateTo',
            'sort',
            'month',
            'priceBox',
            'priceLeaflet'
        ));
    }
}
