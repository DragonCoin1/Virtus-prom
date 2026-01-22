@extends('layouts.app')
@section('title', 'Корректировка зарплаты')

@section('content')
<h3 class="mb-3">Редактировать корректировку</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('salary.adjustments.update', $salaryAdjustment) }}" class="card">
    @csrf
    @method('PUT')

    <div class="card-body">
        <div class="row g-3">
            @if(isset($user) && app(\App\Services\AccessService::class)->isDeveloper($user) && isset($cities))
                <div class="col-md-4">
                    <label class="form-label">Город <span class="text-danger">*</span></label>
                    <select class="form-select" name="city_id" required>
                        <option value="">— выбрать —</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->city_id }}" @selected(old('city_id', $salaryAdjustment->city_id)==$city->city_id)>
                                {{ $city->city_name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Укажите город, к которому относится корректировка</small>
                </div>
            @endif
            
            <div class="col-md-4">
                <label class="form-label">Промоутер</label>
                <select class="form-select" name="promoter_id" required>
                    <option value="">— выбрать —</option>
                    @foreach($promoters as $p)
                        <option value="{{ $p->promoter_id }}" @selected(old('promoter_id', $salaryAdjustment->promoter_id)==$p->promoter_id)>
                            {{ $p->promoter_full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Дата</label>
                <input class="form-control" type="date" name="adj_date"
                       value="{{ old('adj_date', $salaryAdjustment->adj_date) }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Сумма (+ / -)</label>
                <input class="form-control" type="number" name="amount"
                       value="{{ old('amount', $salaryAdjustment->amount) }}" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Комментарий</label>
                <input class="form-control" name="comment" value="{{ old('comment', $salaryAdjustment->comment) }}">
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('salary.index') }}">Назад</a>
    </div>
</form>
@endsection
