@extends('layouts.app')
@section('title', 'Разноска')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Разноска</h3>
    @if(!empty($canEdit))
        <a class="btn btn-primary btn-sm" href="{{ route('route_actions.create') }}">+ Добавить</a>
    @endif
</div>

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
@endphp

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('module.route_actions') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Промоутер</label>
                <select class="form-select" name="promoter_id">
                    <option value="">— все —</option>
                    @foreach($promoters as $p)
                        <option value="{{ $p->promoter_id }}" @selected((string)$promoterId === (string)$p->promoter_id)>
                            {{ $p->promoter_full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Маршрут</label>
                <select class="form-select" name="route_id">
                    <option value="">— все —</option>
                    @foreach($routes as $r)
                        <option value="{{ $r->route_id }}" @selected((string)$routeId === (string)$r->route_id)>
                            {{ $r->route_code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="{{ route('module.route_actions') }}">Сброс</a>
            </div>
        </form>
    </div>

    @if(!empty($hasFilters))
        <div class="card-footer d-flex justify-content-between align-items-center">
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
                <th>Макеты</th>
                <th>Листовки</th>
                <th>Расклейка</th>
                <th>Визитки</th>
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
                    <td>{{ $templatesText($a->templates) }}</td>
                    <td>{{ $a->leaflets_total }}</td>
                    <td>{{ $a->posters_total }}</td>
                    <td>{{ $a->cards_count }}</td>
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
