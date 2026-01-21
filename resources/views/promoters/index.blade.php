@extends('layouts.app')
@section('title', 'Промоутеры')

@php
    $statusMap = [
        'active' => 'Активен',
        'trainee' => 'Стажёр',
        'paused' => 'Пауза',
        'fired' => 'Уволен',
    ];

    $sort = request('sort');
    $dir = request('dir');
    $isSorted = $sort === 'full_name';
    $currentDir = $isSorted ? $dir : null;
    $nextDir = $currentDir === 'asc' ? 'desc' : ($currentDir === 'desc' ? null : 'asc');
    $baseParams = request()->except(['sort', 'dir', 'page']);
    $sortParams = $nextDir ? array_merge($baseParams, ['sort' => 'full_name', 'dir' => $nextDir]) : $baseParams;
    $sortUrl = route('module.promoters', $sortParams);
    $sortIcon = $currentDir === 'asc' ? '▲' : ($currentDir === 'desc' ? '▼' : '');
@endphp

@section('content')
<div class="vp-toolbar mb-3">
    <h3 class="m-0">Промоутеры</h3>
    <div class="vp-toolbar-actions">
        <a class="btn btn-primary btn-sm vp-btn" href="{{ route('promoters.create') }}">Добавить</a>
        <a class="btn btn-outline-primary btn-sm vp-btn" href="{{ route('promoters.import.form') }}">Импорт</a>
    </div>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<form class="vp-filter vp-filter-compact vp-filter-stack mb-3" method="GET" action="{{ route('module.promoters') }}">
    <div class="vp-filter-fields">
        <input class="form-control form-control-sm vp-filter-input" name="search"
               placeholder="Поиск: имя или телефон" value="{{ request('search') }}">
        <select class="form-select form-select-sm vp-filter-status vp-filter-status-short" name="status">
            <option value="">Статус: все</option>
            <option value="active" @selected(request('status')==='active')>Активен</option>
            <option value="trainee" @selected(request('status')==='trainee')>Стажёр</option>
            <option value="paused" @selected(request('status')==='paused')>Пауза</option>
            <option value="fired" @selected(request('status')==='fired')>Уволен</option>
        </select>
    </div>
    <div class="vp-filter-actions">
        <button class="btn btn-outline-primary btn-sm vp-btn">Фильтр</button>
        <a class="btn btn-outline-secondary btn-sm vp-btn" href="{{ route('module.promoters') }}">Сброс</a>
    </div>
    @if($isSorted)
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
    @endif
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>
                    <a class="text-decoration-none text-reset" href="{{ $sortUrl }}">
                        ФИО {!! $sortIcon ? '<span class="ms-1 text-muted">' . $sortIcon . '</span>' : '' !!}
                    </a>
                </th>
                <th>Телефон</th>
                <th>Реквизиты</th>
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
                    <td>{{ $p->promoter_requisites ?? '—' }}</td>
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
                <tr><td colspan="8" class="text-center text-muted p-4">Пока нет промоутеров</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $promoters->links() }}
</div>
@endsection
