<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->paginate(20)
            ->withQueryString();

        $dailyDates = $daily->pluck('action_date')->toArray();
        $paymentsByDate = collect();
        if (!empty($dailyDates)) {
            $paymentsByDate = DB::table('route_actions as ra')
                ->leftJoin('promoters as p', 'p.promoter_id', '=', 'ra.promoter_id')
                ->whereIn('ra.action_date', $dailyDates)
                ->groupBy('ra.action_date', 'ra.promoter_id', 'p.promoter_full_name', 'p.promoter_requisites')
                ->select([
                    'ra.action_date',
                    'ra.promoter_id',
                    'p.promoter_full_name',
                    'p.promoter_requisites',
                    DB::raw('SUM(ra.payment_amount) as sum_payment'),
                ])
                ->orderBy('ra.action_date', 'asc')
                ->orderBy('p.promoter_full_name', 'asc')
                ->get()
                ->groupBy('action_date');
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
            'dateFrom',
            'dateTo',
            'sort',
            'month',
            'priceBox',
            'priceLeaflet',
            'paymentsByDate'
        ));
    }
}

