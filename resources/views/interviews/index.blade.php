@extends('layouts.app')
@section('title', 'Собеседования')

@section('content')
@php
    $status = request('status');
    $search = request('search');
    $dateFrom = request('date_from');
    $dateTo = request('date_to');

    $statusMap = [
        'planned' => ['label' => 'Запланировано', 'badge' => 'text-bg-primary'],
        'came' => ['label' => 'Пришёл', 'badge' => 'text-bg-success'],
        'no_show' => ['label' => 'Не пришёл', 'badge' => 'text-bg-secondary'],
        'hired' => ['label' => 'Принят', 'badge' => 'text-bg-success'],
        'rejected' => ['label' => 'Отказ', 'badge' => 'text-bg-danger'],
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Собеседования</h3>
    <a class="btn btn-primary btn-sm" href="{{ route('interviews.create') }}">+ Добавить</a>
</div>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('interviews.index') }}">
            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select class="form-select" name="status">
                    <option value="">— все —</option>
                    <option value="planned" @selected($status==='planned')>Запланировано</option>
                    <option value="came" @selected($status==='came')>Пришёл</option>
                    <option value="no_show" @selected($status==='no_show')>Не пришёл</option>
                    <option value="hired" @selected($status==='hired')>Принят</option>
                    <option value="rejected" @selected($status==='rejected')>Отказ</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Поиск</label>
                <input class="form-control" name="search" value="{{ $search }}" placeholder="ФИО / телефон / источник">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="{{ route('interviews.index') }}">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Дата</th>
                <th>Кандидат</th>
                <th style="width: 160px;">Телефон</th>
                <th style="width: 160px;">Источник</th>
                <th style="width: 170px;">Статус</th>
                <th>Комментарий</th>
                <th style="width: 90px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($interviews as $i)
                @php
                    $s = $statusMap[$i->status] ?? ['label'=>$i->status, 'badge'=>'text-bg-light'];
                @endphp
                <tr>
                    <td>{{ $i->interview_date }}</td>
                    <td class="fw-semibold">{{ $i->candidate_name }}</td>
                    <td>{{ $i->candidate_phone ?? '—' }}</td>
                    <td>{{ $i->source ?? '—' }}</td>
                    <td><span class="badge {{ $s['badge'] }}">{{ $s['label'] }}</span></td>
                    <td>{{ $i->comment ?? '—' }}</td>
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
                </tr>
            @endforeach

            @if($interviews->count() === 0)
                <tr>
                    <td colspan="7" class="text-center text-muted p-4">Пока нет собеседований</td>
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
