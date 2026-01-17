@extends('layouts.app')
@section('title', 'Маршруты')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Маршруты</h3>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('routes.import.form') }}">Импорт</a>
        <a class="btn btn-primary btn-sm" href="{{ route('routes.create') }}">+ Добавить</a>
    </div>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

@php
    $search = request('search');
    $type = request('type');

    $hasArea = \Illuminate\Support\Facades\Schema::hasColumn('routes', 'route_area');
@endphp

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('routes.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Поиск</label>
                <input class="form-control" name="search" value="{{ $search }}" placeholder="Код или район">
            </div>

            <div class="col-md-3">
                <label class="form-label">Тип</label>
                <select class="form-select" name="type">
                    <option value="">— все —</option>
                    <option value="city" @selected($type==='city')>Город</option>
                    <option value="private" @selected($type==='private')>Частный сектор</option>
                    <option value="mixed" @selected($type==='mixed')>Смешанный</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="{{ route('routes.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Код</th>
                @if($hasArea)
                    <th>Район</th>
                @endif
                <th style="width: 160px;">Тип</th>
                <th style="width: 90px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($routes as $r)
                <tr>
                    <td class="fw-semibold">{{ $r->route_code }}</td>
                    @if($hasArea)
                        <td>{{ $r->route_area }}</td>
                    @endif
                    <td>
                        @php
                            $map = ['city'=>'Город','private'=>'Частный сектор','mixed'=>'Смешанный'];
                        @endphp
                        {{ $map[$r->route_type] ?? $r->route_type }}
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('routes.edit', $r) }}">Править</a>
                    </td>
                </tr>
            @endforeach

            @if($routes->count() === 0)
                <tr>
                    <td colspan="{{ $hasArea ? 4 : 3 }}" class="text-center text-muted p-4">
                        Нет маршрутов
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $routes->links() }}
</div>
@endsection
