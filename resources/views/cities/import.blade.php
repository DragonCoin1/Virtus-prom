@extends('layouts.app')
@section('title', 'Импорт городов')

@section('content')
<h3 class="mb-3">Импорт городов (CSV/JSON)</h3>

@if (isset($errors) && is_object($errors) && $errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

@if(session('errors') && is_array(session('errors')) && count(session('errors')) > 0)
    <div class="alert alert-warning">
        <strong>Пропущенные города:</strong>
        <ul class="mb-0 mt-2">
            @foreach(session('errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-3">
            <div class="fw-bold mb-2">Формат CSV (разделитель ; )</div>
            <div class="text-muted small mb-2">Первая строка — заголовки. Обязательные колонки:</div>
            <pre class="mb-0">city_name;region_name;population;is_active</pre>
            <div class="text-muted small mt-2">
                <strong>city_name</strong> — название города (обязательно)<br>
                <strong>region_name</strong> — название региона (необязательно)<br>
                <strong>population</strong> — население (необязательно)<br>
                <strong>is_active</strong> — активен ли город (1 или 0, по умолчанию 1)
            </div>
            <div class="text-muted small mt-2">
                Пример строки: <code>Москва;Москва;13010112;1</code>
            </div>
        </div>

        <div class="mb-0">
            <div class="fw-bold mb-2">Формат JSON</div>
            <div class="text-muted small mb-2">Массив объектов с полями:</div>
            <pre class="mb-0">[
  {
    "city_name": "Москва",
    "region_name": "Москва",
    "population": 13010112,
    "is_active": true
  },
  {
    "city_name": "Санкт-Петербург",
    "region_name": "Ленинградская область",
    "population": 5600000,
    "is_active": true
  }
]</pre>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('cities.import') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body">
        <label class="form-label">Файл (CSV или JSON)</label>
        <input class="form-control" type="file" name="file" accept=".csv,.txt,.json" required>
        <div class="form-text">Поддерживаются форматы: CSV (разделитель ;) и JSON</div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Загрузить</button>
        <a class="btn btn-outline-secondary" href="{{ route('cities.index') }}">Назад</a>
    </div>
</form>
@endsection
