@extends('layouts.app')

@section('title', 'Новая инструкция')

@section('content')
<div class="container">
    <h1 class="h4 mb-3">Новая инструкция</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('instructions.store') }}" class="card card-body">
        @csrf

        <div class="mb-3">
            <label class="form-label">Название</label>
            <input name="title" class="form-control" value="{{ old('title') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Текст</label>
            <textarea name="body" class="form-control" rows="8" required>{{ old('body') }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a class="btn btn-outline-secondary" href="{{ route('instructions.index') }}">Назад</a>
        </div>
    </form>
</div>
@endsection
