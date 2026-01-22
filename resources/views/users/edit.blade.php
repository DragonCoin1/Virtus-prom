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
            @php
                $userRoleName = \Illuminate\Support\Facades\DB::table('roles')->where('role_id', $user->role_id)->value('role_name');
                $isMultipleCities = $userRoleName === 'regional_director' || $userRoleName === 'branch_director';
            @endphp
            @if($isMultipleCities)
                <div class="col-md-12 mb-3">
                    <label class="form-label">Города <span class="text-danger">*</span></label>
                    <div id="cities-selector">
                        <div class="mb-2 d-flex gap-2">
                            <div class="vp-city-autocomplete flex-grow-1">
                                <input type="text" 
                                       class="form-control vp-city-input" 
                                       placeholder="Добавить город" 
                                       autocomplete="off"
                                       data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'
                                       data-multiple-cities="true"
                                       id="city-selector-input">
                                <input type="hidden" class="vp-city-id">
                                <div class="vp-city-autocomplete-dropdown"></div>
                            </div>
                            <button type="button" class="btn btn-primary" id="add-city-manually-btn">Добавить</button>
                        </div>
                        <div id="selected-cities-list" class="d-flex flex-wrap gap-2">
                            @foreach($selectedCityIds ?? [] as $cityId)
                                @php
                                    $city = $cities->firstWhere('city_id', $cityId);
                                @endphp
                                @if($city)
                                    <span class="badge bg-primary d-inline-flex align-items-center gap-2">
                                        {{ $city->city_name }}
                                        <button type="button" class="btn-close btn-close-white" style="font-size: 0.7em;" data-city-id="{{ $city->city_id }}"></button>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                        <input type="hidden" name="city_ids" id="city-ids-input" value="{{ implode(',', $selectedCityIds ?? []) }}">
                    </div>
                    <small class="text-muted">Выберите города, для которых пользователь будет иметь доступ</small>
                </div>
            @else
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
                               data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'
                               id="single-city-input-edit">
                        <input type="hidden" name="city_id" class="vp-city-id" value="{{ old('city_id', $user->city_id) }}">
                        <div class="vp-city-autocomplete-dropdown"></div>
                    </div>
                </div>
            @endif
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

@push('scripts')
<script>
    // Управление выбором нескольких городов для regional_director
    const roleSelect = document.querySelector('select[name="role_id"]');
    const citySelectorDiv = document.getElementById('cities-selector');
    const singleCityDiv = document.querySelector('.col-md-6:has(.vp-city-autocomplete)');
    
    let selectedCities = [];
    
    // Инициализация выбранных городов из скрытого поля
    const cityIdsInput = document.getElementById('city-ids-input');
    if (cityIdsInput && cityIdsInput.value) {
        const cityIds = cityIdsInput.value.split(',').filter(id => id).map(id => parseInt(id));
        const cityInput = document.getElementById('city-selector-input');
        if (cityInput) {
            const citiesData = JSON.parse(cityInput.getAttribute('data-cities') || '[]');
            selectedCities = cityIds.map(id => {
                const city = citiesData.find(c => c.id === id);
                return city ? { id: city.id, name: city.name } : null;
            }).filter(c => c !== null);
        }
    }
    
    function toggleCitySelector() {
        const selectedRole = roleSelect.value;
        if (!selectedRole) {
            if (citySelectorDiv) citySelectorDiv.closest('.col-md-12')?.style.setProperty('display', 'none');
            if (singleCityDiv) singleCityDiv.style.setProperty('display', 'block');
            return;
        }
        
        // Проверяем, является ли роль regional_director или branch_director по тексту опции
        const option = roleSelect.options[roleSelect.selectedIndex];
        const optionText = option.text.toLowerCase();
        const isMultipleCities = optionText.includes('regional') || 
                                optionText.includes('региональн') ||
                                (optionText.includes('директор') && !optionText.includes('генеральн') && !optionText.includes('региональн'));
        if (citySelectorDiv) citySelectorDiv.closest('.col-md-12').style.display = isMultipleCities ? 'block' : 'none';
        if (singleCityDiv) singleCityDiv.style.display = isMultipleCities ? 'none' : 'block';
    }
    
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleCitySelector);
        toggleCitySelector(); // Инициализация
    }
    
    // Обработка выбора города
    const cityInput = document.getElementById('city-selector-input');
    const selectedCitiesList = document.getElementById('selected-cities-list');
    
    if (cityInput && cityIdsInput && selectedCitiesList) {
        // Обновляем список при загрузке
        updateSelectedCitiesList();
        
        // Ждем инициализации autocomplete
        setTimeout(() => {
            const cityAutocomplete = cityInput.closest('.vp-city-autocomplete');
            if (cityAutocomplete) {
                const hiddenInput = cityAutocomplete.querySelector('.vp-city-id');
                const dropdown = cityAutocomplete.querySelector('.vp-city-autocomplete-dropdown');
                
                // Функция для поиска города по названию
                function findCityByName(cityName) {
                    const citiesDataStr = cityInput.getAttribute('data-cities');
                    if (!citiesDataStr) return null;
                    
                    const citiesData = JSON.parse(citiesDataStr);
                    const normalizedName = cityName.trim().toLowerCase();
                    
                    // Ищем точное совпадение
                    let found = citiesData.find(c => c.name.toLowerCase() === normalizedName);
                    if (found) return found;
                    
                    // Ищем частичное совпадение (начинается с)
                    found = citiesData.find(c => c.name.toLowerCase().startsWith(normalizedName));
                    if (found) return found;
                    
                    // Ищем вхождение
                    found = citiesData.find(c => c.name.toLowerCase().includes(normalizedName));
                    return found || null;
                }
                
                // Функция для обработки выбора города
                function handleCitySelection(cityId, cityName) {
                    if (!cityId || selectedCities.find(c => c.id === cityId)) {
                        return false;
                    }
                    
                    selectedCities.push({ id: cityId, name: cityName });
                    updateSelectedCitiesList();
                    
                    // Добавляем город к текущему значению через запятую, не очищаем поле
                    const currentValue = cityInput.value.trim();
                    if (currentValue) {
                        // Если есть запятые, заменяем последнюю часть на выбранный город
                        const parts = currentValue.split(',');
                        if (parts.length > 1) {
                            // Заменяем последнюю часть на выбранный город
                            parts[parts.length - 1] = cityName;
                            cityInput.value = parts.join(', ') + ', ';
                        } else {
                            // Если запятых нет, добавляем город через запятую
                            cityInput.value = currentValue + ', ' + cityName + ', ';
                        }
                    } else {
                        // Если поле пустое, просто добавляем город
                        cityInput.value = cityName + ', ';
                    }
                    
                    hiddenInput.value = '';
                    dropdown.classList.remove('show');
                    
                    // Фокусируемся на поле, чтобы можно было продолжить ввод
                    setTimeout(() => {
                        cityInput.focus();
                        // Устанавливаем курсор в конец
                        const len = cityInput.value.length;
                        cityInput.setSelectionRange(len, len);
                    }, 10);
                    
                    return true;
                }
                
                // Функция для ручного добавления города
                function addCityManually() {
                    const currentValue = cityInput.value.trim();
                    if (!currentValue) return;
                    
                    // Если есть запятые, берем только последнюю часть
                    const parts = currentValue.split(',');
                    const cityNameToAdd = parts[parts.length - 1].trim();
                    
                    if (!cityNameToAdd) return;
                    
                    // Ищем город в списке доступных
                    const foundCity = findCityByName(cityNameToAdd);
                    if (!foundCity) {
                        alert('Город "' + cityNameToAdd + '" не найден в списке доступных городов');
                        return;
                    }
                    
                    // Добавляем город
                    if (handleCitySelection(foundCity.id, foundCity.name)) {
                        // Успешно добавлен
                    }
                }
                
                // Обработчик кнопки "Добавить"
                const addCityBtn = document.getElementById('add-city-manually-btn');
                if (addCityBtn) {
                    addCityBtn.addEventListener('click', addCityManually);
                }
                
                // Обработчик Enter для ручного добавления
                cityInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.target.closest('.vp-city-autocomplete-dropdown')) {
                        e.preventDefault();
                        addCityManually();
                    }
                });
                
                // Перехватываем события на уровне dropdown с capture phase
                // Это гарантирует, что наш обработчик выполнится раньше основного
                dropdown.addEventListener('click', (e) => {
                    const item = e.target.closest('.vp-city-autocomplete-item');
                    if (item && item.dataset.cityId) {
                        const cityId = parseInt(item.dataset.cityId);
                        const cityName = item.dataset.cityName || item.textContent.trim();
                        
                        if (cityId && handleCitySelection(cityId, cityName)) {
                            // Блокируем дальнейшую обработку события
                            e.stopImmediatePropagation();
                            e.preventDefault();
                            return false;
                        }
                    }
                }, true); // capture phase - выполнится первым
            }
        }, 100);
    }
    
    function updateSelectedCitiesList() {
        const selectedCitiesListEl = document.getElementById('selected-cities-list');
        const cityIdsInputEl = document.getElementById('city-ids-input');
        if (!selectedCitiesListEl || !cityIdsInputEl) return;
        
        selectedCitiesListEl.innerHTML = '';
        const cityIds = [];
        
        selectedCities.forEach(city => {
            cityIds.push(city.id);
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary d-inline-flex align-items-center gap-2';
            badge.innerHTML = `
                ${city.name}
                <button type="button" class="btn-close btn-close-white" style="font-size: 0.7em;" data-city-id="${city.id}"></button>
            `;
            selectedCitiesListEl.appendChild(badge);
        });
        
        cityIdsInputEl.value = cityIds.join(',');
        
        // Обработка удаления города
        selectedCitiesListEl.querySelectorAll('.btn-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const cityId = parseInt(btn.dataset.cityId);
                selectedCities = selectedCities.filter(c => c.id !== cityId);
                updateSelectedCitiesList();
            });
        });
    }
    
    // Вызываем обновление списка при загрузке страницы
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(updateSelectedCitiesList, 200);
        });
    } else {
        setTimeout(updateSelectedCitiesList, 200);
    }
    
</script>
@endpush
@endsection
