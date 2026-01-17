@extends('layouts.app')
@section('title', 'Добавить разноску')

@section('content')
<h3 class="mb-3">Добавить разноску</h3>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('route_actions.store') }}" class="card">
    @csrf

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Дата</label>
                <input class="form-control" type="date" name="action_date" required
                       value="{{ old('action_date', date('Y-m-d')) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Промоутер</label>
                <select class="form-select" name="promoter_id" required>
                    <option value="">— выбрать —</option>
                    @foreach($promoters as $p)
                        <option value="{{ $p->promoter_id }}" @selected(old('promoter_id')==$p->promoter_id)>
                            {{ $p->promoter_full_name }}{{ $p->promoter_phone ? ' (' . $p->promoter_phone . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Маршрут</label>
                <select class="form-select" name="route_id" required>
                    <option value="">— выбрать —</option>
                    @foreach($routes as $r)
                        <option value="{{ $r->route_id }}" @selected(old('route_id')==$r->route_id)>
                            {{ $r->route_code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Макеты листовок</label>

                <div class="position-relative" id="templatesSelect">
                    <button type="button" class="btn btn-outline-secondary w-100 text-start" id="templatesBtn">
                        <span id="templatesSelectedText" class="text-truncate d-inline-block" style="max-width: calc(100% - 10px);">
                            — выбрать —
                        </span>
                    </button>

                    <div class="border rounded bg-white shadow-sm p-2 mt-1 d-none"
                         id="templatesMenu"
                         style="position:absolute; z-index: 20; width: 100%; max-height: 260px; overflow:auto;">
                        @foreach($leafletTemplates as $t)
                            <div class="form-check">
                                <input class="form-check-input tmpl-check"
                                       type="checkbox"
                                       name="leaflet_template_ids[]"
                                       value="{{ $t->template_id }}"
                                       id="tmpl_{{ $t->template_id }}"
                                       data-name="{{ $t->template_name }}"
                                       @checked(is_array(old('leaflet_template_ids')) && in_array($t->template_id, old('leaflet_template_ids')))>
                                <label class="form-check-label" for="tmpl_{{ $t->template_id }}">
                                    {{ $t->template_name }}
                                </label>
                            </div>
                        @endforeach

                        @if($leafletTemplates->count() === 0)
                            <div class="text-muted p-1">Нет активных макетов</div>
                        @endif

                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-primary" id="templatesDone">Готово</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="templatesClear">Очистить</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Листовки (сделано)</label>
                <input class="form-control" type="number" min="0" name="leaflets_total"
                       value="{{ old('leaflets_total', 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Листовки (выдано)</label>
                <input class="form-control" type="number" min="0" name="leaflets_issued"
                       value="{{ old('leaflets_issued', 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Расклейка (сделано)</label>
                <input class="form-control" type="number" min="0" name="posters_total"
                       value="{{ old('posters_total', 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Расклейка (выдано)</label>
                <input class="form-control" type="number" min="0" name="posters_issued"
                       value="{{ old('posters_issued', 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Визитки (сделано)</label>
                <input class="form-control" type="number" min="0" name="cards_count"
                       value="{{ old('cards_count', 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Визитки (выдано)</label>
                <input class="form-control" type="number" min="0" name="cards_issued"
                       value="{{ old('cards_issued', 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Ящики</label>
                <input class="form-control" type="number" min="0" name="boxes_done"
                       value="{{ old('boxes_done', 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Оплата</label>
                <input class="form-control" type="number" min="0" name="payment_amount"
                       value="{{ old('payment_amount', 0) }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Комментарий</label>
                <input class="form-control" name="action_comment" value="{{ old('action_comment') }}">
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="{{ route('module.route_actions') }}">Назад</a>
    </div>
</form>

<script>
(function () {
    const root = document.getElementById('templatesSelect');
    if (!root) return;

    const btn = document.getElementById('templatesBtn');
    const menu = document.getElementById('templatesMenu');
    const done = document.getElementById('templatesDone');
    const clear = document.getElementById('templatesClear');
    const text = document.getElementById('templatesSelectedText');

    function isOpen(){ return !menu.classList.contains('d-none'); }
    function open(){ menu.classList.remove('d-none'); }
    function close(){ menu.classList.add('d-none'); }

    function updateText(){
        const checks = root.querySelectorAll('.tmpl-check:checked');
        const names = [];
        checks.forEach(c => {
            const n = c.getAttribute('data-name');
            if (n) names.push(n);
        });
        text.textContent = names.length ? names.join(', ') : '— выбрать —';
    }

    btn.addEventListener('click', () => { isOpen() ? close() : open(); });
    done.addEventListener('click', () => { close(); updateText(); });

    clear.addEventListener('click', () => {
        const checks = root.querySelectorAll('.tmpl-check');
        checks.forEach(c => c.checked = false);
        updateText();
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) { close(); updateText(); }
    });

    updateText();
})();
</script>
@endsection
