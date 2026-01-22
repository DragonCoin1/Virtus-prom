@extends('layouts.app')

@section('title', 'Остатки рекламы')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Остатки рекламы</h1>
        @if(!empty($canEditModules['ad_residuals']))
            <a href="{{ route('ad_residuals.create') }}" class="btn btn-primary">Добавить</a>
        @endif
    </div>

    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <form class="row g-2 mb-3" method="GET" action="{{ route('ad_residuals.index') }}">
        <div class="col-md-3">
            <select class="form-select" name="branch_id">
                <option value="">Все филиалы</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->branch_id }}" {{ (string) request('branch_id') === (string) $branch->branch_id ? 'selected' : '' }}>
                        {{ $branch->branch_name }} ({{ $branch->city?->city_name }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input class="form-control" name="ad_type" placeholder="Тип рекламы" value="{{ request('ad_type') }}">
        </div>
        <div class="col-md-2">
            <input class="form-control" type="date" name="received_from" value="{{ request('received_from') }}">
        </div>
        <div class="col-md-2">
            <input class="form-control" type="date" name="received_to" value="{{ request('received_to') }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary">Фильтр</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Филиал</th>
                <th>Тип</th>
                <th>Получено</th>
                <th>Остаток</th>
                <th>Дата</th>
                <th>Комментарий</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($residuals as $residual)
                <tr>
                    <td>{{ $residual->branch?->branch_name ?? '—' }}</td>
                    <td>{{ $residual->ad_type }}</td>
                    <td>{{ $residual->ad_amount }}</td>
                    <td>{{ $residual->remaining_amount }}</td>
                    <td>{{ $residual->received_at?->format('d.m.Y') }}</td>
                    <td>{{ $residual->notes ?? '—' }}</td>
                    <td class="text-end">
                        @if(!empty($canEditModules['ad_residuals']))
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('ad_residuals.edit', $residual) }}">Изменить</a>
                            <form method="POST" action="{{ route('ad_residuals.destroy', $residual) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить запись?')">Удалить</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Данных нет</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $residuals->links() }}
</div>
@endsection
