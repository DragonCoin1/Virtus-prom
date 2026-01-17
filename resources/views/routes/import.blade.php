@extends('layouts.app')
@section('title', 'Импорт маршрутов')

@section('content')
<h3 class="mb-3">Импорт маршрутов (CSV)</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-2 fw-bold">Формат CSV (разделитель ; )</div>
        <div class="text-muted small mb-2">Первая строка — заголовки. Нужны колонки:</div>
        <pre class="mb-0">route_code;route_district;route_type;boxes_count;entrances_count;is_active;route_comment</pre>
        <div class="text-muted small mt-2">
            route_type: city / private / mixed<br>
            is_active: 1 или 0
        </div>
    </div>
</div>

<form method="POST" action="{{ route('routes.import') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body">
        <label class="form-label">CSV файл</label>
        <input class="form-control" type="file" name="csv_file" accept=".csv,.txt" required>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Загрузить</button>
        <a class="btn btn-outline-secondary" href="{{ route('routes.index') }}">Назад</a>
    </div>
</form>
@endsection
