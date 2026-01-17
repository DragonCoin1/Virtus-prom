@extends('layouts.app')
@section('title', 'Править макет')

@section('content')
<h3 class="mb-3">Править макет</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('ad_templates.update', $adTemplate) }}" class="card">
    @csrf
    @method('PUT')

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Название</label>
                <input class="form-control" name="template_name" required
                       value="{{ old('template_name', $adTemplate->template_name) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Тип</label>
                <select class="form-select" name="template_type" required>
                    <option value="leaflet" @selected(old('template_type', $adTemplate->template_type)==='leaflet')>Листовка</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('ad_templates.index') }}">Назад</a>
    </div>
</form>
@endsection
