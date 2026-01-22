@extends('layouts.app')
@section('title', 'Карты')

@section('content')
@php
    $typeMap = [
        'city' => 'Город',
        'private' => 'Частный сектор',
        'mixed' => 'Смешанный',
    ];
@endphp

<div class="vp-toolbar mb-3">
    <h3 class="mb-0">Карты</h3>
    <div class="vp-toolbar-actions">
        @php
            // Проверяем права через canAccessModule
            $canManageRoutes = false;
            $canViewTemplates = false;
            if (auth()->check()) {
                $accessService = app(\App\Services\AccessService::class);
                $user = auth()->user();
                $canManageRoutes = $accessService->canAccessModule($user, 'routes', 'edit');
                $canViewTemplates = $accessService->canAccessModule($user, 'ad_templates', 'view');
            }
        @endphp
        @if($canManageRoutes)
            <a href="{{ route('routes.create') }}" class="btn btn-sm btn-outline-primary vp-btn">+ Маршрут</a>
            <a href="{{ route('routes.import.form') }}" class="btn btn-sm btn-primary vp-btn">Импорт</a>
        @endif
        @if($canViewTemplates)
            <a class="btn btn-outline-primary btn-sm vp-btn" href="{{ route('ad_templates.index') }}">Макеты</a>
        @endif
    </div>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

@php
    $showCityFilter = false;
    $currentUser = $user ?? auth()->user();
    if ($currentUser) {
        $accessService = app(\App\Services\AccessService::class);
        $showCityFilter = $accessService->isDeveloper($currentUser) || $accessService->isGeneralDirector($currentUser) || $accessService->isRegionalDirector($currentUser);
    }
@endphp

@if($showCityFilter && $cities->isNotEmpty())
    @php
        $selectedCity = $cities->firstWhere('city_id', request('city_id'));
    @endphp
    <div class="card mb-3">
        <div class="card-body">
            <form class="vp-filter vp-filter-compact vp-filter-stack" method="GET" action="{{ route('module.cards') }}" id="cardsCityFilter">
                <div class="vp-filter-fields">
                    <div class="vp-filter-group vp-city-autocomplete">
                        <input type="text" 
                               class="form-control form-control-sm vp-city-input" 
                               placeholder="Город" 
                               value="{{ $selectedCity?->city_name ?? '' }}"
                               autocomplete="off"
                               data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'
                               onchange="document.getElementById('cardsCityFilter').submit()">
                        <input type="hidden" name="city_id" class="vp-city-id" value="{{ request('city_id') }}">
                        <div class="vp-city-autocomplete-dropdown"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width: 48px;"></th>
                    <th>Код</th>
                    <th>Район</th>
                    <th>Тип</th>
                    <th class="text-end">Ящики</th>
                    <th class="text-end">Подъезды</th>
                    <th>Последнее прохождение</th>
                    <th>Давность</th>
                    <th class="text-end" style="width: 140px;">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse($routes as $r)
                    @php
                        $typeText = $typeMap[$r->route_type] ?? $r->route_type ?? '—';
                    @endphp
                    <tr>
                        <td class="text-center">
                            <span class="vp-dot {{ $r->is_stale ? 'vp-dot-purple' : 'vp-dot-green' }}"></span>
                        </td>
                        <td class="fw-semibold">{{ $r->route_code }}</td>
                        <td class="text-muted">{{ $r->route_district ?? '—' }}</td>
                        <td class="text-muted">{{ $typeText }}</td>
                        <td class="text-end">{{ (int) $r->boxes_count }}</td>
                        <td class="text-end">{{ (int) $r->entrances_count }}</td>
                        <td>
                            @if($r->last_action_date_parsed)
                                {{ $r->last_action_date_parsed->format('d.m.Y') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $r->age_label }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    ⋮
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if($canManageRoutes)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('routes.edit', $r->route_id) }}">
                                                Редактировать
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ route('module.route_actions', ['route_id' => $r->route_id]) }}">
                                            Разноска по маршруту
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Маршрутов нет</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($routes->hasPages())
        <div class="card-footer">
            {{ $routes->links() }}
        </div>
    @endif
</div>
@endsection
