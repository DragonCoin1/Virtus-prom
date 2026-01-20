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
@endphp

<div class="vp-filter mb-3">
    <form method="GET" action="{{ route('module.route_actions') }}" class="vp-filter-form">
        <div class="vp-filter-fields">
            <div class="vp-filter-group">
                <span class="vp-filter-label">Дата</span>
                <span class="vp-filter-inline">с</span>
                <input type="date" class="form-control form-control-sm vp-filter-date" name="date_from" value="{{ $dateFrom }}">
                <span class="vp-filter-inline">по</span>
                <input type="date" class="form-control form-control-sm vp-filter-date" name="date_to" value="{{ $dateTo }}">
            </div>

            <div class="vp-filter-group">
                <input type="text" class="form-control form-control-sm vp-filter-search"
                       placeholder="Промоутер" data-filter-target="promoterSelect">
                <select class="form-select form-select-sm vp-filter-select" name="promoter_id" id="promoterSelect">
                    <option value="">Промоутер</option>
                    @foreach($promoters as $p)
                        <option value="{{ $p->promoter_id }}" @selected((string)$promoterId === (string)$p->promoter_id)>
                            {{ $p->promoter_full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="vp-filter-group">
                <input type="text" class="form-control form-control-sm vp-filter-search"
                       placeholder="Маршрут" data-filter-target="routeSelect">
                <select class="form-select form-select-sm vp-filter-select" name="route_id" id="routeSelect">
                    <option value="">Маршрут</option>
                    @foreach($routes as $r)
                        <option value="{{ $r->route_id }}" @selected((string)$routeId === (string)$r->route_id)>
                            {{ $r->route_code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="vp-filter-group">
                <button class="btn btn-primary btn-sm">Показать</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('module.route_actions') }}">Сброс</a>
            </div>
        </div>

        @if(!empty($canEdit))
            <a class="btn btn-primary btn-sm vp-filter-add" href="{{ route('route_actions.create') }}" aria-label="Добавить запись">+</a>
        @endif
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

@push('scripts')
<script>
    document.querySelectorAll('[data-filter-target]').forEach((input) => {
        const targetId = input.getAttribute('data-filter-target');
        const select = document.getElementById(targetId);
        if (!select) {
            return;
        }

        const options = Array.from(select.options);
        options.forEach((option) => {
            option.dataset.label = option.textContent.toLowerCase();
        });

        let timer;
        input.addEventListener('input', (event) => {
            const value = event.target.value.trim().toLowerCase();
            clearTimeout(timer);
            timer = setTimeout(() => {
                options.forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = value.length > 0 && !option.dataset.label.includes(value);
                });
            }, 200);
        });
    });
</script>
@endpush
