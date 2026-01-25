@extends('layouts.app')

@section('title', 'Остатки рекламы')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Остатки рекламы</h1>
        @php
            // Для developer всегда показываем кнопку
            $canAddResidual = false;
            if (auth()->check()) {
                $accessService = app(\App\Services\AccessService::class);
                if ($accessService->isDeveloper(auth()->user())) {
                    $canAddResidual = true;
                } elseif (!empty($canEditModules['ad_residuals'])) {
                    $canAddResidual = true;
                }
            }
        @endphp
        @if($canAddResidual)
            <a href="{{ route('ad_residuals.create') }}" class="btn btn-primary">+ Внести приход рекламы</a>
        @endif
    </div>

    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    @php
        $showCityFilter = false;
        if ($user ?? false) {
            $accessService = app(\App\Services\AccessService::class);
            $showCityFilter = $accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user);
        }
    @endphp
    <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('ad_residuals.index') }}">
        @if($showCityFilter && ($cities ?? collect())->isNotEmpty())
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
        <div class="col-md-2">
            <label class="form-label">Тип рекламы</label>
            <select class="form-select form-select-sm" name="ad_type">
                <option value="">Все</option>
                <option value="листовки" @selected(request('ad_type') === 'листовки')>Листовки</option>
                <option value="визитки" @selected(request('ad_type') === 'визитки')>Визитки</option>
                <option value="расклейка" @selected(request('ad_type') === 'расклейка')>Расклейка</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Дата с</label>
            <input class="form-control form-control-sm" type="date" name="received_from" value="{{ request('received_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Дата по</label>
            <input class="form-control form-control-sm" type="date" name="received_to" value="{{ request('received_to') }}">
        </div>
        <div class="col-md-2">
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary flex-fill">Фильтр</button>
                <a class="btn btn-sm btn-outline-secondary flex-fill" href="{{ route('ad_residuals.index') }}">Сброс</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Город</th>
                <th>Тип</th>
                <th>Получено</th>
                <th>Остаток</th>
                <th>Дата</th>
                <th>Комментарий</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($residuals as $residual)
                <tr>
                    <td>{{ $residual->branch?->city?->city_name ?? '—' }}</td>
                    <td>
                        @if($residual->ad_type === 'листовки')
                            Листовки
                        @elseif($residual->ad_type === 'расклейка')
                            Расклейка
                        @elseif($residual->ad_type === 'визитки')
                            Визитки
                        @else
                            {{ $residual->ad_type }}
                        @endif
                    </td>
                    <td>{{ $residual->ad_amount }}</td>
                    <td><strong>{{ $residual->calculated_remaining ?? $residual->remaining_amount }}</strong></td>
                    <td>{{ $residual->received_at?->format('d.m.Y') }}</td>
                    <td>{{ $residual->notes ?? '—' }}</td>
                    <td class="text-end">
                        @if($canAddResidual)
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('ad_residuals.edit', $residual) }}">Изменить</a>
                            <form method="POST" action="{{ route('ad_residuals.destroy', $residual) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить запись?')">Удалить</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Данных нет</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $residuals->links() }}
@endsection
