@extends('layouts.app')
@section('title', 'Зарплата')

@section('content')
@php
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $promoterId = request('promoter_id');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Зарплата</h3>
    @if(!empty($canEdit))
        <a class="btn btn-primary btn-sm" href="{{ route('salary.adjustments.create') }}">+ Корректировка</a>
    @endif
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('salary.index') }}">
            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
            </div>

            <div class="col-md-4">
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

            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="{{ route('salary.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>Промоутер</th>
                <th style="width: 160px;">По разноске</th>
                <th style="width: 160px;">Корректировки</th>
                <th style="width: 160px;">Итого</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td class="fw-semibold">{{ $r['promoter_name'] }}</td>
                    <td>{{ $r['sum_payment'] }}</td>
                    <td>{{ $r['sum_adj'] }}</td>
                    <td class="fw-semibold">{{ $r['sum_final'] }}</td>
                </tr>
            @endforeach

            @if(count($rows) === 0)
                <tr><td colspan="4" class="text-center text-muted p-4">Нет данных за выбранный период</td></tr>
            @endif
            </tbody>

            @if(count($rows) > 0)
                <tfoot>
                <tr>
                    <th>Итого</th>
                    <th>{{ $totalPayment }}</th>
                    <th>{{ $totalAdj }}</th>
                    <th>{{ $totalFinal }}</th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Последние корректировки
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Дата</th>
                <th>Промоутер</th>
                <th style="width: 140px;">Сумма</th>
                <th>Комментарий</th>
                <th style="width: 160px;">Внёс</th>
                <th style="width: 90px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($lastAdjustments as $a)
                <tr>
                    <td>{{ $a->adj_date }}</td>
                    <td>{{ $a->promoter?->promoter_full_name ?? ('ID ' . $a->promoter_id) }}</td>
                    <td class="{{ (int)$a->amount < 0 ? 'text-danger' : 'text-success' }}">
                        {{ $a->amount }}
                    </td>
                    <td>{{ $a->comment ?? '—' }}</td>
                    <td>{{ $a->createdBy?->user_login ?? '—' }}</td>
                    <td class="text-end">
                        @if(!empty($canEdit))
                            <form method="POST"
                                  action="{{ route('salary.adjustments.destroy', $a) }}"
                                  onsubmit="return confirm('Удалить корректировку?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">×</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach

            @if($lastAdjustments->count() === 0)
                <tr><td colspan="6" class="text-center text-muted p-4">Пока нет корректировок</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
