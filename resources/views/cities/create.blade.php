@extends('layouts.app')
@section('title', 'Добавить город')

@section('content')
<div class="vp-toolbar mb-3">
    <h3 class="m-0">Добавить город</h3>
    <a class="btn btn-outline-secondary btn-sm vp-btn" href="{{ route('cities.index') }}">Назад</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('cities.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Название города <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('city_name') is-invalid @enderror" 
                           name="city_name" 
                           value="{{ old('city_name') }}" 
                           required>
                    @error('city_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Регион</label>
                    <input type="text" 
                           class="form-control @error('region_name') is-invalid @enderror" 
                           name="region_name" 
                           value="{{ old('region_name') }}">
                    @error('region_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Население</label>
                    <input type="number" 
                           class="form-control @error('population') is-invalid @enderror" 
                           name="population" 
                           value="{{ old('population') }}" 
                           min="0">
                    @error('population')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Статус</label>
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="is_active" 
                               value="1" 
                               id="is_active"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Активен
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary vp-btn">Сохранить</button>
                <a class="btn btn-outline-secondary vp-btn" href="{{ route('cities.index') }}">Отмена</a>
            </div>
        </form>
    </div>
</div>
@endsection
