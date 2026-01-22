@extends('layouts.app')
@section('title', 'Города')

@section('content')
<div class="vp-toolbar mb-3">
    <h3 class="m-0">Города</h3>
    <a class="btn btn-primary btn-sm vp-btn" href="{{ route('cities.import.form') }}">Импорт</a>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

@if(session('errors') && is_array(session('errors')) && count(session('errors')) > 0)
    <div class="alert alert-warning">
        <strong>Пропущенные города:</strong>
        <ul class="mb-0 mt-2">
            @foreach(session('errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form class="vp-filter vp-filter-compact vp-filter-stack" method="GET" action="{{ route('cities.index') }}">
            <div class="vp-filter-fields">
                <div class="row g-2 w-100">
                    <div class="col-md-4">
                        <label class="form-label">Поиск</label>
                        <input class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Название города или региона">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Статус</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">— все —</option>
                            <option value="active" @selected(request('status')==='active')>Активные</option>
                            <option value="inactive" @selected(request('status')==='inactive')>Неактивные</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Население от</label>
                        <input type="number" class="form-control form-control-sm" name="min_population" value="{{ request('min_population') }}" placeholder="300000" min="0">
                    </div>
                </div>
            </div>

            <div class="vp-filter-actions">
                <button class="btn btn-outline-primary btn-sm vp-btn">Показать</button>
                <a class="btn btn-outline-secondary btn-sm vp-btn" href="{{ route('cities.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>Город</th>
                <th>Регион</th>
                <th style="width: 150px;">Население</th>
                <th style="width: 100px;">Статус</th>
            </tr>
            </thead>
            <tbody>
            @foreach($cities as $city)
                <tr>
                    <td class="fw-semibold">{{ $city->city_name }}</td>
                    <td>{{ $city->region_name ?? '—' }}</td>
                    <td>
                        @if($city->population)
                            {{ number_format($city->population, 0, ',', ' ') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($city->is_active)
                            <span class="badge bg-success">Активен</span>
                        @else
                            <span class="badge bg-secondary">Неактивен</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            @if($cities->count() === 0)
                <tr>
                    <td colspan="4" class="text-center text-muted p-4">Пока нет городов</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $cities->links() }}
</div>
@endsection
