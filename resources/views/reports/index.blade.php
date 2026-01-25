@extends('layouts.app')
@section('title', 'Отчёты')

@section('content')
@php
    $sortLabel = $sort === 'asc' ? 'Старые → новые' : 'Новые → старые';
@endphp

<div class="vp-toolbar mb-3">
    <h3 class="m-0">Отчёты</h3>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>Сводка за текущий месяц</div>
        <div class="text-muted" style="font-size: 12px;">
            {{ $month['from'] }} — {{ $month['to'] }}
        </div>
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <div class="text-muted">Листовки</div>
                <div class="fs-5 fw-semibold">{{ $month['leaflets'] }}</div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Ящики</div>
                <div class="fs-5 fw-semibold">{{ $month['boxes'] }}</div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Расклейка</div>
                <div class="fs-5 fw-semibold">{{ $month['posters'] }}</div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Визитки</div>
                <div class="fs-5 fw-semibold">{{ $month['cards'] }}</div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Оплата</div>
                <div class="fs-5 fw-semibold">{{ $month['payment'] }}</div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Цена ящика</div>
                <div class="fs-5 fw-semibold">{{ $priceBox === null ? '—' : $priceBox }}</div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Цена листовки</div>
                <div class="fs-5 fw-semibold">{{ $priceLeaflet === null ? '—' : $priceLeaflet }}</div>
            </div>

            <div class="col-md-10">
                <div class="text-muted mb-1">Выдано за месяц</div>
                <div class="d-flex gap-3 flex-wrap">
                    <div>Листовки: <strong>{{ $month['leaflets_issued'] }}</strong></div>
                    <div>Расклейка: <strong>{{ $month['posters_issued'] }}</strong></div>
                    <div>Визитки: <strong>{{ $month['cards_issued'] }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="vp-filter vp-filter-compact vp-filter-stack" method="GET" action="{{ route('reports.index') }}">
            <div class="vp-filter-fields">
                <div class="row g-2 w-100">
                    @php
                        $showCityFilter = false;
                        $currentUser = $user ?? auth()->user();
                        if ($currentUser) {
                            $accessService = app(\App\Services\AccessService::class);
                            $showCityFilter = $accessService->isDeveloper($currentUser) || $accessService->isGeneralDirector($currentUser) || $accessService->isRegionalDirector($currentUser) || $accessService->isBranchDirector($currentUser);
                        }
                    @endphp
                    @if($showCityFilter && $cities->isNotEmpty())
                        @php
                            $selectedCity = $cities->firstWhere('city_id', request('city_id'));
                        @endphp
                        <div class="col-md-3">
                            <label class="form-label">Город</label>
                            <div class="vp-city-autocomplete">
                                <input type="text" 
                                       class="form-control form-control-sm vp-city-input" 
                                       placeholder="Город" 
                                       value="{{ $selectedCity?->city_name ?? '' }}"
                                       autocomplete="off"
                                       data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'>
                                <input type="hidden" name="city_id" class="vp-city-id" value="{{ request('city_id') }}">
                                <div class="vp-city-autocomplete-dropdown"></div>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Дата с</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" value="{{ $dateFrom }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Дата по</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" value="{{ $dateTo }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Сортировка</label>
                        <select class="form-select form-select-sm" name="sort">
                            <option value="desc" @selected($sort==='desc')>Новые → старые</option>
                            <option value="asc" @selected($sort==='asc')>Старые → новые</option>
                        </select>
                    </div>

                    <div class="col-md-12 text-muted" style="font-size: 12px;">
                        Сейчас: {{ $dateFrom }} — {{ $dateTo }} • {{ $sortLabel }}
                    </div>
                </div>
            </div>

            <div class="vp-filter-actions">
                <button class="btn btn-outline-primary btn-sm vp-btn">Показать</button>
                <a class="btn btn-outline-secondary btn-sm vp-btn" href="{{ route('reports.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Дата</th>

                <th style="width: 120px;">Листовки</th>
                <th style="width: 120px;">Ящики</th>
                <th style="width: 120px;">Расклейка</th>
                <th style="width: 120px;">Визитки</th>
                <th style="width: 140px;">Оплата</th>

                <th style="width: 140px;">Выдано листовок</th>
                <th style="width: 140px;">Выдано расклеек</th>
                <th style="width: 140px;">Выдано визиток</th>
            </tr>
            </thead>
            <tbody>
            @foreach($daily as $d)
                <tr>
                    <td class="fw-semibold">{{ $d->action_date }}</td>

                    <td>{{ (int)$d->sum_leaflets }}</td>
                    <td>{{ (int)$d->sum_boxes }}</td>
                    <td>{{ (int)$d->sum_posters }}</td>
                    <td>{{ (int)$d->sum_cards }}</td>
                    <td class="fw-semibold">{{ (int)$d->sum_payment }}</td>

                    <td>{{ (int)$d->sum_leaflets_issued }}</td>
                    <td>{{ (int)$d->sum_posters_issued }}</td>
                    <td>{{ (int)$d->sum_cards_issued }}</td>
                </tr>
            @endforeach

            @if($daily->count() === 0)
                <tr>
                    <td colspan="9" class="text-center text-muted p-4">Нет данных за выбранный период</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        Оплата промоутерам по дням
    </div>
    <div class="card-body">
        @php
            $paymentsByDay = $promoterPayments->groupBy('action_date');
        @endphp

        @forelse($paymentsByDay as $day => $entries)
            <div class="mb-3">
                <div class="fw-semibold">{{ $day }}</div>
                <ul class="list-unstyled mb-0">
                    @foreach($entries as $entry)
                        <li>
                            {{ $entry->promoter_full_name }} {{ $entry->promoter_requisites ?? '—' }} — <strong>{{ (int)$entry->sum_payment }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="text-muted">Нет данных по оплате за выбранный период.</div>
        @endforelse
    </div>
</div>

<div class="mt-3">
    {{ $daily->links() }}
</div>
@endsection
