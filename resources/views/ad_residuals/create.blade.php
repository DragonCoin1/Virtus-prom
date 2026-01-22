@extends('layouts.app')

@section('title', 'Добавить остатки')

@section('content')
<div class="container">
    <h1 class="h4 mb-3">Добавить остатки</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('ad_residuals.store') }}" class="card card-body">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Филиал</label>
                <select name="branch_id" class="form-select" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->branch_id }}" {{ (string) old('branch_id') === (string) $branch->branch_id ? 'selected' : '' }}>
                            {{ $branch->branch_name }} ({{ $branch->city?->city_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Тип рекламы</label>
                <select name="ad_type" class="form-select" required>
                    <option value="">— выберите —</option>
                    <option value="листовки" {{ old('ad_type') === 'листовки' ? 'selected' : '' }}>Листовки</option>
                    <option value="расклейка" {{ old('ad_type') === 'расклейка' ? 'selected' : '' }}>Расклейка</option>
                    <option value="визитки" {{ old('ad_type') === 'визитки' ? 'selected' : '' }}>Визитки</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Получено (количество)</label>
                <input type="number" min="0" name="ad_amount" class="form-control" value="{{ old('ad_amount') }}" required>
                <small class="text-muted">Остаток будет рассчитан автоматически: приход - расход из разноски</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Дата получения</label>
                <input type="date" name="received_at" class="form-control" value="{{ old('received_at') }}" required>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Комментарий</label>
                <input name="notes" class="form-control" value="{{ old('notes') }}">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a class="btn btn-outline-secondary" href="{{ route('ad_residuals.index') }}">Назад</a>
        </div>
    </form>
</div>
@endsection
