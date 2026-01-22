@extends('layouts.app')
@section('title', 'Собеседования')

@section('content')
@php
    $status = request('status');
    $search = request('search');
    $dateFrom = request('date_from');
    $dateTo = request('date_to');

    $statusMap = [
        'planned' => ['label' => 'Запланировано', 'badge' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle'],
        'came' => ['label' => 'Пришёл', 'badge' => 'bg-success-subtle text-success-emphasis border border-success-subtle'],
        'no_show' => ['label' => 'Не пришёл', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
        'hired' => ['label' => 'Принят', 'badge' => 'bg-info-subtle text-info-emphasis border border-info-subtle'],
        'rejected' => ['label' => 'Отказ', 'badge' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle'],
    ];
@endphp

<div class="vp-toolbar mb-3">
    <h3 class="m-0">Собеседования</h3>
    <div class="vp-toolbar-actions">
        @php
            // Проверяем права через canAccessModule
            $canAddInterview = false;
            if (auth()->check()) {
                $accessService = app(\App\Services\AccessService::class);
                $user = auth()->user();
                $canAddInterview = $accessService->canAccessModule($user, 'interviews', 'edit');
            }
        @endphp
        @if($canAddInterview)
            <a class="btn btn-primary btn-sm vp-btn" href="{{ route('interviews.create') }}">+ Добавить</a>
        @endif
    </div>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form class="vp-filter vp-filter-compact vp-filter-stack" method="GET" action="{{ route('interviews.index') }}">
            <div class="vp-filter-fields">
                <div class="row g-2 w-100">
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
                    <div class="col-md-2">
                        <label class="form-label">Поиск</label>
                        <input class="form-control form-control-sm" name="search" value="{{ $search }}" placeholder="ФИО / телефон / источник">
                    </div>

                    <div class="col-md-2 col-lg-1">
                        <label class="form-label">Статус</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">— все —</option>
                            <option value="planned" @selected($status==='planned')>Запланировано</option>
                            <option value="came" @selected($status==='came')>Пришёл</option>
                            <option value="no_show" @selected($status==='no_show')>Не пришёл</option>
                            <option value="hired" @selected($status==='hired')>Принят</option>
                            <option value="rejected" @selected($status==='rejected')>Отказ</option>
                        </select>
                    </div>

                    <div class="col-md-auto">
                        <label class="form-label">Дата с</label>
                        <input type="date" class="form-control form-control-sm vp-filter-date" name="date_from" value="{{ $dateFrom }}">
                    </div>

                    <div class="col-md-auto">
                        <label class="form-label">Дата по</label>
                        <input type="date" class="form-control form-control-sm vp-filter-date" name="date_to" value="{{ $dateTo }}">
                    </div>
                </div>
            </div>

            <div class="vp-filter-actions">
                <button class="btn btn-outline-primary btn-sm vp-btn">Показать</button>
                <a class="btn btn-outline-secondary btn-sm vp-btn" href="{{ route('interviews.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 120px;">Дата</th>
                <th style="width: 100px;">Время</th>
                <th>Кандидат</th>
                <th style="width: 160px;">Телефон</th>
                <th style="width: 160px;">Источник</th>
                <th style="width: 170px;">Статус</th>
                <th>Комментарий</th>
                @php
                    // Проверяем права через canAccessModule
                    $canEditInterview = false;
                    if (auth()->check()) {
                        $accessService = app(\App\Services\AccessService::class);
                        $user = auth()->user();
                        $canEditInterview = $accessService->canAccessModule($user, 'interviews', 'edit');
                    }
                @endphp
                @if($canEditInterview)
                    <th style="width: 90px;"></th>
                @endif
            </tr>
            </thead>
            <tbody>
            @foreach($interviews as $i)
                @php
                    $s = $statusMap[$i->status] ?? ['label'=>$i->status, 'badge'=>'text-bg-light'];
                @endphp
                <tr>
                    <td>{{ $i->interview_date }}</td>
                    <td class="text-muted">{{ $i->interview_time ? substr($i->interview_time, 0, 5) : '—' }}</td>
                    <td class="fw-semibold">{{ $i->candidate_name }}</td>
                    <td>{{ $i->candidate_phone ?? '—' }}</td>
                    <td>{{ $i->source ?? '—' }}</td>
                    <td><span class="badge rounded-pill {{ $s['badge'] }}">{{ $s['label'] }}</span></td>
                    <td>{{ $i->comment ?? '—' }}</td>
                    @if($canEditInterview)
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    ⋮
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('interviews.edit', $i) }}">Править</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST"
                                              action="{{ route('interviews.destroy', $i) }}"
                                              onsubmit="return confirm('Удалить собеседование?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit">Удалить</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach

            @if($interviews->count() === 0)
                <tr>
                    <td colspan="{{ $canEditInterview ? 8 : 7 }}" class="text-center text-muted p-4">Пока нет собеседований</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $interviews->links() }}
</div>
@endsection
