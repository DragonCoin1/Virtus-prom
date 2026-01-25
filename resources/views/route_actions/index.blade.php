@extends('layouts.app')
@section('title', 'Разноска')

@section('content')
@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

@php
    $templatesText = function($templates) {
        if (!$templates || $templates->count() === 0) return '—';
        return $templates->pluck('template_name')->implode(', ');
    };

    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $promoterId = request('promoter_id');
    $routeId = request('route_id');
    $status = request('status');
    $promoterSelected = $promoters->firstWhere('promoter_id', $promoterId);
    $routeSelected = $routes->firstWhere('route_id', $routeId);
@endphp

<div class="vp-filter mb-3">
    <form method="GET" action="{{ route('module.route_actions') }}" class="vp-filter-form vp-filter-stack">
        <div class="vp-filter-fields">
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
                <div class="vp-filter-group vp-city-autocomplete">
                    <input type="text" 
                           class="form-control form-control-sm vp-city-input" 
                           placeholder="Город" 
                           value="{{ $selectedCity?->city_name ?? '' }}"
                           autocomplete="off"
                           data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'>
                    <input type="hidden" name="city_id" class="vp-city-id" value="{{ request('city_id') }}">
                    <div class="vp-city-autocomplete-dropdown"></div>
                </div>
            @endif
            <div class="vp-filter-group">
                <span class="vp-filter-label">Дата</span>
                <span class="vp-filter-inline">с</span>
                <input type="date" class="form-control form-control-sm vp-filter-date" name="date_from" value="{{ $dateFrom }}">
                <span class="vp-filter-inline">по</span>
                <input type="date" class="form-control form-control-sm vp-filter-date" name="date_to" value="{{ $dateTo }}">
            </div>

            <div class="vp-filter-group">
                <input type="text" class="form-control form-control-sm vp-filter-search"
                       placeholder="Промоутер" list="promotersList"
                       value="{{ $promoterSelected?->promoter_full_name }}"
                       data-searchable-select data-hidden-target="promoterId">
                <input type="hidden" name="promoter_id" id="promoterId" value="{{ $promoterId }}">
                <datalist id="promotersList">
                    @foreach($promoters as $p)
                        <option value="{{ $p->promoter_full_name }}" data-id="{{ $p->promoter_id }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="vp-filter-group">
                <select class="form-select form-select-sm vp-filter-status vp-filter-status-compact" name="status">
                    <option value="">Статус: все</option>
                    <option value="active" @selected($status==='active')>Активен</option>
                    <option value="trainee" @selected($status==='trainee')>Стажёр</option>
                    <option value="paused" @selected($status==='paused')>Пауза</option>
                    <option value="fired" @selected($status==='fired')>Уволен</option>
                </select>
            </div>

            <div class="vp-filter-group">
                <input type="text" class="form-control form-control-sm vp-filter-search"
                       placeholder="Маршрут" list="routesList"
                       value="{{ $routeSelected?->route_code }}"
                       data-searchable-select data-hidden-target="routeId">
                <input type="hidden" name="route_id" id="routeId" value="{{ $routeId }}">
                <datalist id="routesList">
                    @foreach($routes as $r)
                        <option value="{{ $r->route_code }}" data-id="{{ $r->route_id }}"></option>
                    @endforeach
                </datalist>
            </div>
            @if(!empty($canEdit))
                <div class="vp-filter-group ms-auto">
                    <a class="btn btn-primary btn-sm vp-btn" href="{{ route('route_actions.create') }}">Добавить</a>
                </div>
            @endif
        </div>

        <div class="vp-filter-actions">
            <button class="btn btn-outline-primary btn-sm vp-btn">Показать</button>
            <a class="btn btn-outline-secondary btn-sm vp-btn" href="{{ route('module.route_actions') }}">Сброс</a>
        </div>
    </form>

    @if(!empty($hasFilters))
        <div class="vp-filter-summary">
            <div class="text-muted">
                Итого оплата: <strong>{{ (int)($sumPayment ?? 0) }}</strong>
            </div>
            <div class="text-muted">
                Записей: <strong>{{ $actions->total() }}</strong>
            </div>
        </div>
    @endif
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>Дата</th>
                <th>Промоутер</th>
                <th>Маршрут</th>
                <th>Город</th>
                <th>Макеты</th>
                <th>Листовки<br><span class="text-muted" style="font-size: 12px;">сделано / выдано</span></th>
                <th>Расклейка<br><span class="text-muted" style="font-size: 12px;">сделано / выдано</span></th>
                <th>Визитки<br><span class="text-muted" style="font-size: 12px;">сделано / выдано</span></th>
                <th>Ящики</th>
                <th>Оплата</th>
                <th>Комментарий</th>
                <th>Внёс</th>
                @if(!empty($canEdit))
                    <th style="width: 80px;"></th>
                @endif
            </tr>
            </thead>
            <tbody>
            @foreach($actions as $a)
                <tr>
                    <td>{{ $a->action_date }}</td>
                    <td>{{ $a->promoter?->promoter_full_name ?? ('ID ' . $a->promoter_id) }}</td>
                    <td>{{ $a->route?->route_code ?? ('ID ' . $a->route_id) }}</td>
                    <td>{{ $a->route?->city?->city_name ?? $a->promoter?->branch?->city?->city_name ?? '—' }}</td>
                    <td>{{ $templatesText($a->templates) }}</td>
                    <td>
                        <div class="fw-semibold">{{ $a->leaflets_total }}</div>
                        <div class="text-muted" style="font-size: 12px;">выдано {{ $a->leaflets_issued ?? 0 }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $a->posters_total }}</div>
                        <div class="text-muted" style="font-size: 12px;">выдано {{ $a->posters_issued ?? 0 }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $a->cards_count }}</div>
                        <div class="text-muted" style="font-size: 12px;">выдано {{ $a->cards_issued ?? 0 }}</div>
                    </td>
                    <td>{{ $a->boxes_done }}</td>
                    <td>{{ $a->payment_amount ?? 0 }}</td>
                    <td>{{ $a->action_comment }}</td>
                    <td>{{ $a->createdBy?->user_login ?? '—' }}</td>

                    @if(!empty($canEdit))
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                        title="Действия">
                                    ⋮
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('route_actions.edit', $a) }}">
                                            Править
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST"
                                              action="{{ route('route_actions.destroy', $a) }}"
                                              onsubmit="return confirm('Удалить запись разноски?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit">
                                                Удалить
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach

            @if($actions->count() === 0)
                <tr>
                    <td colspan="{{ !empty($canEdit) ? 12 : 11 }}" class="text-center text-muted p-4">
                        Пока нет записей разноски
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $actions->links() }}
</div>
@endsection
