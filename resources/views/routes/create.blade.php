@extends('layouts.app')
@section('title', 'Добавить маршрут')

@section('content')
<h3 class="mb-3">Добавить маршрут</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('routes.store') }}" class="card">
    @csrf
    <div class="card-body">
        @include('routes.form', ['route' => null])
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('routes.index') }}">Назад</a>
    </div>
</form>
@endsection
