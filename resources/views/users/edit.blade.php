@extends('layouts.app')

@section('title', 'Редактировать пользователя')

@section('content')
<div class="container">
    <h1 class="h4 mb-3">Редактировать пользователя</h1>

    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user) }}" class="card card-body">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Логин</label>
                <input name="user_login" class="form-control" value="{{ old('user_login', $user->user_login) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">ФИО</label>
                <input name="user_full_name" class="form-control" value="{{ old('user_full_name', $user->user_full_name) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Роль</label>
                <select name="role_id" class="form-select" required>
                    @foreach($roles as $roleId => $roleName)
                        <option value="{{ $roleId }}" {{ (string) old('role_id', $user->role_id) === (string) $roleId ? 'selected' : '' }}>{{ $roleName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Новый пароль</label>
                <input type="password" name="password" class="form-control" placeholder="Оставьте пустым, чтобы не менять">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Город</label>
                @php
                    $selectedCity = $cities->firstWhere('city_id', old('city_id', $user->city_id));
                @endphp
                <div class="vp-city-autocomplete">
                    <input type="text" 
                           class="form-control vp-city-input" 
                           placeholder="Город" 
                           value="{{ $selectedCity?->city_name ?? '' }}"
                           autocomplete="off"
                           data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'>
                    <input type="hidden" name="city_id" class="vp-city-id" value="{{ old('city_id', $user->city_id) }}">
                    <div class="vp-city-autocomplete-dropdown"></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Филиал</label>
                <select name="branch_id" class="form-select">
                    <option value="">—</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->branch_id }}" {{ (string) old('branch_id', $user->branch_id) === (string) $branch->branch_id ? 'selected' : '' }}>{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Активен</label>
                <select name="user_is_active" class="form-select">
                    <option value="1" {{ (string) old('user_is_active', $user->user_is_active) === '1' ? 'selected' : '' }}>Да</option>
                    <option value="0" {{ (string) old('user_is_active', $user->user_is_active) === '0' ? 'selected' : '' }}>Нет</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Назад</a>
        </div>
    </form>
</div>
@endsection
