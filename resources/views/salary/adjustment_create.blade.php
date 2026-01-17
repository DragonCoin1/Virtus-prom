@extends('layouts.app')
@section('title', 'Корректировка зарплаты')

@section('content')
<h3 class="mb-3">Добавить корректировку</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('salary.adjustments.store') }}" class="card">
    @csrf

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Промоутер</label>
                <select class="form-select" name="promoter_id" required>
                    <option value="">— выбрать —</option>
                    @foreach($promoters as $p)
                        <option value="{{ $p->promoter_id }}" @selected(old('promoter_id')==$p->promoter_id)>
                            {{ $p->promoter_full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Дата</label>
                <input class="form-control" type="date" name="adj_date"
                       value="{{ old('adj_date', date('Y-m-d')) }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Сумма (+ / -)</label>
                <input class="form-control" type="number" name="amount" value="{{ old('amount', 0) }}" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Комментарий</label>
                <input class="form-control" name="comment" value="{{ old('comment') }}">
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('salary.index') }}">Назад</a>
    </div>
</form>
@endsection
