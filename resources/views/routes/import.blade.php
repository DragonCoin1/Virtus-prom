@extends('layouts.app')
@section('title', 'Импорт маршрутов')

@section('content')
<h3 class="mb-3">Импорт маршрутов</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-3">
            <div class="mb-2 fw-bold">Формат CSV (разделитель ; )</div>
            <div class="text-muted small mb-2">Первая строка — заголовки. Нужны колонки:</div>
            <pre class="mb-0">route_code;route_district;route_type;boxes_count;entrances_count;is_active;route_comment</pre>
            <div class="text-muted small mt-2">
                route_code — код маршрута (например, мск-1)<br>
                route_district — район (например, ЦАО)<br>
                route_type — тип (city = город, private = частный сектор, mixed = смешанный)<br>
                boxes_count — количество ящиков (например, 345)<br>
                entrances_count — количество подъездов (например, 120)<br>
                is_active — активен ли маршрут (1 или 0)<br>
                route_comment — комментарий (необязательно)
            </div>
            <div class="text-muted small mt-2">
                Пример строки: <code>мск-1;ЦАО;city;345;120;1;Маршрут мск-1(345)</code>
            </div>
        </div>
        
        <hr>
        
        <div class="mb-2 fw-bold">Формат JSON</div>
        <div class="text-muted small mb-2">Массив объектов с полями:</div>
        <pre class="mb-0">[
  {
    "route_code": "мск-1",
    "route_district": "ЦАО",
    "route_type": "city",
    "boxes_count": 345,
    "entrances_count": 120,
    "is_active": 1,
    "route_comment": "Маршрут мск-1(345)"
  }
]</pre>
    </div>
</div>

<form method="POST" action="{{ route('routes.import') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Тип файла</label>
            <select class="form-select" name="file_type" required>
                <option value="csv" {{ old('file_type', 'csv') === 'csv' ? 'selected' : '' }}>CSV</option>
                <option value="json" {{ old('file_type') === 'json' ? 'selected' : '' }}>JSON</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Файл</label>
            <input class="form-control" type="file" name="file" accept=".csv,.json,.txt" required>
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Загрузить</button>
        <a class="btn btn-outline-secondary" href="{{ route('module.cards') }}">Назад</a>
    </div>
</form>
@endsection
