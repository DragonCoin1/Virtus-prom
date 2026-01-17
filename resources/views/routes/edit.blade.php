@extends('layouts.app')
@section('title', 'Редактировать маршрут')

@section('content')
<h3 class="mb-3">Редактировать маршрут</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('routes.update', $route) }}" class="card">
    @csrf
    @method('PUT')

    <div class="card-body">
        @include('routes.form', ['route' => $route])
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('routes.index') }}">Назад</a>
    </div>
</form>
@endsection
