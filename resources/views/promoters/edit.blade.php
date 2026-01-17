@extends('layouts.app')
@section('title', 'Редактировать промоутера')

@section('content')
<h3 class="mb-3">Редактировать промоутера</h3>

@if ($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('promoters.update', $promoter) }}" class="card">
    @csrf
    @method('PUT')

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">ФИО</label>
                <input class="form-control" name="promoter_full_name" required
                       value="{{ old('promoter_full_name', $promoter->promoter_full_name) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Телефон (10 цифр, без +7)</label>
                <input class="form-control" name="promoter_phone" placeholder="9001112233"
                       value="{{ old('promoter_phone', $promoter->promoter_phone) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Статус</label>
                @php $v = old('promoter_status', $promoter->promoter_status); @endphp
                <select class="form-select" name="promoter_status" required>
                    <option value="active" @selected($v==='active')>Активен</option>
                    <option value="trainee" @selected($v==='trainee')>Стажёр</option>
                    <option value="paused" @selected($v==='paused')>Пауза</option>
                    <option value="fired" @selected($v==='fired')>Уволен</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Дата найма</label>
                <input class="form-control" type="date" name="hired_at"
                       value="{{ old('hired_at', $promoter->hired_at) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Дата увольнения</label>
                <input class="form-control" type="date" name="fired_at"
                       value="{{ old('fired_at', $promoter->fired_at) }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Комментарий</label>
                <input class="form-control" name="promoter_comment"
                       value="{{ old('promoter_comment', $promoter->promoter_comment) }}">
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('module.promoters') }}">Назад</a>
    </div>
</form>
@endsection
