@extends('layouts.app')

@section('title', 'Инструкции')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Инструкции</h1>
            <small class="text-muted">Документы и инструкции для работы промоутеров и менеджеров</small>
        </div>
        @if(!empty($canEditModules['instructions']))
            <a href="{{ route('instructions.create') }}" class="btn btn-primary">Добавить</a>
        @endif
    </div>

    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <form class="row g-2 mb-3" method="GET" action="{{ route('instructions.index') }}">
        <div class="col-md-4">
            <input class="form-control" name="search" placeholder="Поиск по названию" value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">Все статусы</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Активные</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Неактивные</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-secondary">Фильтр</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Название</th>
                <th>Статус</th>
                <th>Создана</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($instructions as $instruction)
                <tr>
                    <td>{{ $instruction->title }}</td>
                    <td>{{ $instruction->is_active ? 'Активна' : 'Неактивна' }}</td>
                    <td>{{ $instruction->created_at?->format('d.m.Y') }}</td>
                    <td class="text-end">
                        @if(!empty($canEditModules['instructions']))
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('instructions.edit', $instruction) }}">Изменить</a>
                            <form class="d-inline" method="POST" action="{{ route('instructions.toggle', $instruction) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning">{{ $instruction->is_active ? 'Скрыть' : 'Показать' }}</button>
                            </form>
                            <form class="d-inline" method="POST" action="{{ route('instructions.destroy', $instruction) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить инструкцию?')">Удалить</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Инструкций нет</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $instructions->links() }}
@endsection
