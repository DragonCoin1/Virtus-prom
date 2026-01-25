<div class="row g-3">
    @if(isset($user) && isset($cities) && $cities->isNotEmpty())
        @php
            $selectedCity = $cities->firstWhere('city_id', old('city_id', $route->city_id ?? null));
            $accessService = app(\App\Services\AccessService::class);
            $isRequired = $accessService->isDeveloper($user) || $accessService->isGeneralDirector($user);
        @endphp
        <div class="col-md-4">
            <label class="form-label">Город 
                @if($isRequired)
                    <span class="text-danger">*</span>
                @endif
            </label>
            <div class="vp-city-autocomplete">
                <input type="text" 
                       class="form-control vp-city-input" 
                       placeholder="Город" 
                       value="{{ $selectedCity?->city_name ?? '' }}"
                       autocomplete="off"
                       @if($isRequired) required @endif
                       data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'>
                <input type="hidden" name="city_id" class="vp-city-id" value="{{ old('city_id', $route->city_id ?? null) }}">
                <div class="vp-city-autocomplete-dropdown"></div>
            </div>
            @if($cities->count() === 1)
                <small class="text-muted">Доступен только ваш город</small>
            @endif
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label">Код маршрута</label>
        <input class="form-control" name="route_code" required
               value="{{ old('route_code', $route->route_code ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Район</label>
        <input class="form-control" name="route_district"
               value="{{ old('route_district', $route->route_district ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Тип</label>
        <select class="form-select" name="route_type" required>
            @php $v = old('route_type', $route->route_type ?? 'city'); @endphp
            <option value="city" @selected($v==='city')>Город</option>
            <option value="private" @selected($v==='private')>ЧС</option>
            <option value="mixed" @selected($v==='mixed')>Смешанный</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Ящики (кол-во)</label>
        <input class="form-control" type="number" min="0" name="boxes_count" required
               value="{{ old('boxes_count', $route->boxes_count ?? 0) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Подъезды (кол-во)</label>
        <input class="form-control" type="number" min="0" name="entrances_count" required
               value="{{ old('entrances_count', $route->entrances_count ?? 0) }}">
    </div>

    <div class="col-md-12">
        <label class="form-label">Комментарий</label>
        <input class="form-control" name="route_comment"
               value="{{ old('route_comment', $route->route_comment ?? '') }}">
    </div>

    <div class="col-md-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   @checked(old('is_active', $route->is_active ?? 1) == 1)>
            <label class="form-check-label">Активен</label>
        </div>
    </div>
</div>
