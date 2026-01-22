@extends('layouts.app')

@section('title', 'Редактировать инструкцию')

@section('content')
<div class="container">
    <h1 class="h4 mb-3">Редактировать инструкцию</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('instructions.update', $instruction) }}" class="card card-body">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Название</label>
            <input name="title" class="form-control" value="{{ old('title', $instruction->title) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Текст</label>
            <textarea name="body" class="form-control" rows="8" required>{{ old('body', $instruction->body) }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a class="btn btn-outline-secondary" href="{{ route('instructions.index') }}">Назад</a>
        </div>
    </form>
</div>
@endsection
