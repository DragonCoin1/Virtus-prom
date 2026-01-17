@extends('layouts.app')
@section('title', 'Макеты')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Макеты</h3>
    <a class="btn btn-primary btn-sm" href="{{ route('ad_templates.create') }}">+ Добавить</a>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

@php
    $status = request('status');
    $type = request('type');
    $search = request('search');
@endphp

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('ad_templates.index') }}">
            <div class="col-md-3">
                <label class="form-label">Поиск</label>
                <input class="form-control" name="search" value="{{ $search }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Тип</label>
                <select class="form-select" name="type">
                    <option value="">— все —</option>
                    <option value="leaflet" @selected($type==='leaflet')>Листовка</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select class="form-select" name="status">
                    <option value="">— все —</option>
                    <option value="active" @selected($status==='active')>Активен</option>
                    <option value="inactive" @selected($status==='inactive')>Выключен</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="{{ route('ad_templates.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>Название</th>
                <th>Тип</th>
                <th>Статус</th>
                <th style="width: 120px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($templates as $t)
                <tr>
                    <td>{{ $t->template_name }}</td>
                    <td>{{ $t->template_type === 'leaflet' ? 'Листовка' : $t->template_type }}</td>
                    <td>
                        @if($t->is_active)
                            <span class="badge text-bg-success">Активен</span>
                        @else
                            <span class="badge text-bg-secondary">Выключен</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ⋮
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('ad_templates.edit', $t) }}">Править</a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('ad_templates.toggle', $t) }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">
                                            {{ $t->is_active ? 'Выключить' : 'Включить' }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach

            @if($templates->count() === 0)
                <tr><td colspan="4" class="text-center text-muted p-4">Пока нет макетов</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $templates->links() }}
</div>
@endsection
