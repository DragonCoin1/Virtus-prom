@extends('layouts.app')
@section('title', 'Зарплата')

@section('content')
@php
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $promoterId = request('promoter_id');
    $periodLabel = 'всё время';
    if (!empty($dateFrom) && !empty($dateTo)) {
        $periodLabel = $dateFrom . ' — ' . $dateTo;
    } elseif (!empty($dateFrom)) {
        $periodLabel = 'с ' . $dateFrom;
    } elseif (!empty($dateTo)) {
        $periodLabel = 'до ' . $dateTo;
    }
    $promoterSelected = $promoters->firstWhere('promoter_id', $promoterId);
@endphp

<div class="vp-toolbar mb-3">
    <h3 class="m-0">Зарплата</h3>
    @if(!empty($canEdit))
        <a class="btn btn-primary btn-sm vp-btn" href="{{ route('salary.adjustments.create') }}">+ Корректировка</a>
    @endif
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form class="vp-filter vp-filter-compact vp-filter-stack" method="GET" action="{{ route('salary.index') }}">
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
                        <div class="col-md-2">
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
                    <div class="col-md-auto">
                        <label class="form-label">Дата с</label>
                        <input type="date" class="form-control form-control-sm vp-filter-date" name="date_from" value="{{ $dateFrom }}">
                    </div>

                    <div class="col-md-auto">
                        <label class="form-label">Дата по</label>
                        <input type="date" class="form-control form-control-sm vp-filter-date" name="date_to" value="{{ $dateTo }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Промоутер</label>
                        <input type="text" class="form-control form-control-sm" list="salaryPromotersList"
                               value="{{ $promoterSelected?->promoter_full_name }}"
                               placeholder="Начните вводить ФИО"
                               data-searchable-select data-hidden-target="salaryPromoterId">
                        <input type="hidden" name="promoter_id" id="salaryPromoterId" value="{{ $promoterId }}">
                        <datalist id="salaryPromotersList">
                            @foreach($promoters as $p)
                                <option value="{{ $p->promoter_full_name }}" data-id="{{ $p->promoter_id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>
            </div>

            <div class="vp-filter-actions">
                <button class="btn btn-outline-primary vp-btn">Показать</button>
                <a class="btn btn-outline-secondary vp-btn" href="{{ route('salary.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 160px;">Период</th>
                <th>Промоутер</th>
                <th>Город</th>
                <th style="width: 160px;">По разноске</th>
                <th style="width: 160px;">Корректировки</th>
                <th style="width: 160px;">Итого</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td class="text-muted">{{ $periodLabel }}</td>
                    <td class="fw-semibold">{{ $r['promoter_name'] }}</td>
                    <td>{{ $r['city_name'] ?? '—' }}</td>
                    <td>{{ $r['sum_payment'] }}</td>
                    <td>{{ $r['sum_adj'] }}</td>
                    <td class="fw-semibold">{{ $r['sum_final'] }}</td>
                </tr>
            @endforeach

            @if(count($rows) === 0)
                <tr><td colspan="6" class="text-center text-muted p-4">Нет данных за выбранный период</td></tr>
            @endif
            </tbody>

            @if(count($rows) > 0)
                <tfoot>
                <tr>
                    <th class="text-muted">{{ $periodLabel }}</th>
                    <th>Итого</th>
                    <th></th>
                    <th>{{ $totalPayment }}</th>
                    <th>{{ $totalAdj }}</th>
                    <th>{{ $totalFinal }}</th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Последни корректировки
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Дата</th>
                <th>Промоутер</th>
                <th>Город</th>
                <th style="width: 140px;">Сумма</th>
                <th>Комментарий</th>
                <th style="width: 160px;">Внёс</th>
                <th style="width: 90px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($lastAdjustments as $a)
                <tr>
                    <td>{{ $a->adj_date }}</td>
                    <td>{{ $a->promoter?->promoter_full_name ?? ('ID ' . $a->promoter_id) }}</td>
                    <td>{{ $a->city?->city_name ?? ($a->promoter?->branch?->city?->city_name ?? '—') }}</td>
                    <td class="{{ (int)$a->amount < 0 ? 'text-danger' : 'text-success' }}">
                        {{ $a->amount }}
                    </td>
                    <td>{{ $a->comment ?? '—' }}</td>
                    <td>{{ $a->createdBy?->user_login ?? '—' }}</td>
                    <td class="text-end">
                        @if(!empty($canEdit))
                            <div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('salary.adjustments.edit', $a) }}">
                                    Править
                                </a>
                                <form method="POST"
                                      action="{{ route('salary.adjustments.destroy', $a) }}"
                                      onsubmit="return confirm('Удалить корректировку?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">×</button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach

            @if($lastAdjustments->count() === 0)
                <tr><td colspan="6" class="text-center text-muted p-4">Пока нет корректировок</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-searchable-select]').forEach((input) => {
        const targetId = input.getAttribute('data-hidden-target');
        const hiddenInput = document.getElementById(targetId);
        const listId = input.getAttribute('list');
        const dataList = listId ? document.getElementById(listId) : null;
        if (!hiddenInput || !dataList) {
            return;
        }

        const options = Array.from(dataList.options);

        const syncHidden = () => {
            const value = input.value.trim().toLowerCase();
            const match = options.find((option) => option.value.toLowerCase() === value);
            hiddenInput.value = match ? match.dataset.id : '';
        };

        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(syncHidden, 200);
        });

        input.addEventListener('change', syncHidden);
    });
</script>
@endpush
