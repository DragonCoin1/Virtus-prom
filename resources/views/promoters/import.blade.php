@extends('layouts.app')
@section('title', 'Импорт промоутеров')

@section('content')
<h3 class="mb-3">Импорт промоутеров (CSV)</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-2 fw-bold">Формат CSV (разделитель ; )</div>
        <div class="text-muted small mb-2">Первая строка — заголовки. Нужны колонки:</div>
        <pre class="mb-0">promoter_full_name;promoter_phone;promoter_requisites;promoter_status;hired_at;fired_at;promoter_comment</pre>
        <div class="text-muted small mt-2">
            promoter_status: active / trainee / paused / fired<br>
            даты: YYYY-MM-DD<br>
            promoter_requisites — опционально (если колонка существует в базе)
        </div>
        <div class="text-muted small mt-2">
            Пример строки: <code>Иванов Иван;9001112233;Сбер 1234;active;2026-01-01;;Комментарий</code>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('promoters.import') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body">
        <label class="form-label">CSV файл</label>
        <input class="form-control" type="file" name="csv_file" accept=".csv,.txt" required>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Загрузить</button>
        <a class="btn btn-outline-secondary" href="{{ route('module.promoters') }}">Назад</a>
    </div>
</form>
@endsection
