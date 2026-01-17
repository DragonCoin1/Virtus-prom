@extends('layouts.app')
@section('title', 'Промоутеры')

@php
    $statusMap = [
        'active' => 'Активен',
        'trainee' => 'Стажёр',
        'paused' => 'Пауза',
        'fired' => 'Уволен',
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Промоутеры</h3>
    <div class="d-flex gap-2">
        <a class="btn btn-primary btn-sm" href="{{ route('promoters.create') }}">+ Добавить</a>
    </div>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<form class="row g-2 mb-3" method="GET" action="{{ route('module.promoters') }}">
    <div class="col-md-6">
        <input class="form-control" name="q" placeholder="Поиск: имя или телефон" value="{{ request('q') }}">
    </div>
    <div class="col-md-4">
        <select class="form-select" name="status">
            <option value="">Статус: все</option>
            <option value="active" @selected(request('status')==='active')>Активен</option>
            <option value="trainee" @selected(request('status')==='trainee')>Стажёр</option>
            <option value="paused" @selected(request('status')==='paused')>Пауза</option>
            <option value="fired" @selected(request('status')==='fired')>Уволен</option>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-outline-primary w-100">Фильтр</button>
        <a class="btn btn-outline-secondary w-100" href="{{ route('module.promoters') }}">Сброс</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Статус</th>
                <th>Найм</th>
                <th>Увольнение</th>
                <th>Комментарий</th>
                <th style="width: 80px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($promoters as $p)
                <tr>
                    <td>{{ $p->promoter_full_name }}</td>
                    <td>{{ $p->promoter_phone }}</td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $statusMap[$p->promoter_status] ?? $p->promoter_status }}
                        </span>
                    </td>
                    <td>{{ $p->hired_at }}</td>
                    <td>{{ $p->fired_at }}</td>
                    <td>{{ $p->promoter_comment }}</td>

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
                                    <a class="dropdown-item" href="{{ route('promoters.edit', $p) }}">
                                        Править
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('promoters.destroy', $p) }}"
                                          onsubmit="return confirm('Удалить промоутера: {{ $p->promoter_full_name }} ?');">
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
                </tr>
            @endforeach

            @if($promoters->count() === 0)
                <tr><td colspan="7" class="text-center text-muted p-4">Пока нет промоутеров</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $promoters->links() }}
</div>
@endsection
