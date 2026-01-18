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

<div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Карты</h3>
    <div class="d-flex gap-2">
        <a href="{{ route('routes.create') }}" class="btn btn-sm btn-outline-primary">+ Маршрут</a>
        <a href="{{ route('routes.import.form') }}" class="btn btn-sm btn-primary">Импорт</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('ad_templates.index') }}">Макеты</a>
    </div>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
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
                                    <li>
                                        <a class="dropdown-item" href="{{ route('routes.edit', $r->route_id) }}">
                                            Редактировать
                                        </a>
                                    </li>
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
