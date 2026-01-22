@extends('layouts.app')
@section('title', 'Править собеседование')

@section('content')
<h3 class="mb-3">Править собеседование</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('interviews.update', $interview) }}" class="card">
    @csrf
    @method('PUT')

    <div class="card-body">
        <div class="row g-3">
            @if(isset($showCitySelect) && $showCitySelect && isset($cities) && $cities->isNotEmpty())
                <div class="col-md-4">
                    <label class="form-label">Город</label>
                    <select class="form-select" name="city_id">
                        <option value="">— выбрать —</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->city_id }}" @selected(old('city_id', $interview->city_id)==$city->city_id)>
                                {{ $city->city_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif(isset($user) && (app(\App\Services\AccessService::class)->isManager($user) || app(\App\Services\AccessService::class)->isBranchDirector($user)))
                <div class="col-md-4">
                    <div class="alert alert-info mb-0">
                        <small>Город: {{ $interview->city ? $interview->city->city_name : 'не указан' }}</small>
                        <br><small class="text-muted">Город привязан к вашему филиалу и не может быть изменён</small>
                    </div>
                </div>
            @endif

            <div class="col-md-3">
                <label class="form-label">Дата</label>
                <input class="form-control" type="date" name="interview_date"
                       value="{{ old('interview_date', $interview->interview_date) }}" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Время</label>
                <input class="form-control" type="time" name="interview_time"
                       value="{{ old('interview_time', $interview->interview_time ? substr($interview->interview_time, 0, 5) : '') }}">
            </div>

            <div class="col-md-5">
                <label class="form-label">Кандидат (ФИО)</label>
                <input class="form-control" name="candidate_name"
                       value="{{ old('candidate_name', $interview->candidate_name) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Телефон</label>
                <input class="form-control" name="candidate_phone"
                       value="{{ old('candidate_phone', $interview->candidate_phone) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Источник</label>
                <input class="form-control" name="source"
                       value="{{ old('source', $interview->source) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select class="form-select" name="status" required>
                    <option value="planned" @selected(old('status', $interview->status)==='planned')>Запланировано</option>
                    <option value="came" @selected(old('status', $interview->status)==='came')>Пришёл</option>
                    <option value="no_show" @selected(old('status', $interview->status)==='no_show')>Не пришёл</option>
                    <option value="hired" @selected(old('status', $interview->status)==='hired')>Принят</option>
                    <option value="rejected" @selected(old('status', $interview->status)==='rejected')>Отказ</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Комментарий</label>
                <input class="form-control" name="comment"
                       value="{{ old('comment', $interview->comment) }}">
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('interviews.index') }}">Назад</a>
    </div>
</form>
@endsection
