@extends('layouts.app')

@section('title', 'Создать пользователя')

@section('content')
    <h1 class="h4 mb-3">Создать пользователя</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}" class="card card-body">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Логин</label>
                <input name="user_login" class="form-control" value="{{ old('user_login') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">ФИО</label>
                <input name="user_full_name" class="form-control" value="{{ old('user_full_name') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Роль</label>
                <select name="role_id" class="form-select" required>
                    @foreach($roles as $roleId => $roleName)
                        <option value="{{ $roleId }}" {{ (string) old('role_id') === (string) $roleId ? 'selected' : '' }}>{{ $roleName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            {{-- Поле для выбора нескольких городов (для regional_director) --}}
            <div class="col-md-12 mb-3" id="multiple-cities-field" style="display: none;">
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
                        <!-- Выбранные города будут здесь -->
                    </div>
                    <input type="hidden" name="city_ids" id="city-ids-input" value="">
                </div>
                <small class="text-muted">Выберите города, для которых пользователь будет иметь доступ</small>
            </div>
            
            {{-- Поле для выбора одного города (для остальных ролей) --}}
            <div class="col-md-6 mb-3" id="single-city-field">
                <label class="form-label">Город</label>
                @php
                    $selectedCity = $cities->firstWhere('city_id', old('city_id'));
                @endphp
                <div class="vp-city-autocomplete">
                    <input type="text" 
                           class="form-control vp-city-input" 
                           placeholder="Город" 
                           value="{{ $selectedCity?->city_name ?? '' }}"
                           autocomplete="off"
                           data-cities='@json($cities->map(fn($c) => ['id' => $c->city_id, 'name' => $c->city_name]))'>
                    <input type="hidden" name="city_id" class="vp-city-id" value="{{ old('city_id') }}">
                    <div class="vp-city-autocomplete-dropdown"></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Активен</label>
                <select name="user_is_active" class="form-select">
                    <option value="1" {{ old('user_is_active', '1') === '1' ? 'selected' : '' }}>Да</option>
                    <option value="0" {{ old('user_is_active') === '0' ? 'selected' : '' }}>Нет</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Назад</a>
        </div>
    </form>
@push('scripts')
<script>
    // Управление выбором нескольких городов для regional_director
    const roleSelect = document.querySelector('select[name="role_id"]');
    const multipleCitiesField = document.getElementById('multiple-cities-field');
    const singleCityField = document.getElementById('single-city-field');
    
    // Получаем role_id для regional_director и branch_director из опций
    let regionalDirectorRoleId = null;
    let branchDirectorRoleId = null;
    if (roleSelect) {
        Array.from(roleSelect.options).forEach(option => {
            const text = option.text.toLowerCase();
            if (text.includes('regional') || text.includes('региональн')) {
                regionalDirectorRoleId = option.value;
            }
            if (text.includes('директор') && !text.includes('генеральн') && !text.includes('региональн')) {
                branchDirectorRoleId = option.value;
            }
        });
    }
    
    let selectedCities = [];
    
    function toggleCitySelector() {
        if (!roleSelect) return;
        
        const selectedRole = roleSelect.value;
        if (!selectedRole) {
            if (multipleCitiesField) multipleCitiesField.style.display = 'none';
            if (singleCityField) singleCityField.style.display = 'block';
            return;
        }
        
        // Проверяем, является ли роль regional_director или branch_director
        const option = roleSelect.options[roleSelect.selectedIndex];
        const optionText = option ? option.text.toLowerCase() : '';
        const isMultipleCities = selectedRole === regionalDirectorRoleId || 
                                 selectedRole === branchDirectorRoleId ||
                                 optionText.includes('regional') || 
                                 optionText.includes('региональн') ||
                                 optionText.includes('региональный') ||
                                 (optionText.includes('директор') && !optionText.includes('генеральн') && !optionText.includes('региональн'));
        
        if (multipleCitiesField) {
            multipleCitiesField.style.display = isMultipleCities ? 'block' : 'none';
        }
        if (singleCityField) {
            singleCityField.style.display = isMultipleCities ? 'none' : 'block';
        }
    }
    
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleCitySelector);
        // Инициализация при загрузке страницы
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', toggleCitySelector);
        } else {
            // DOM уже загружен
            toggleCitySelector();
        }
    }
    
    // Обработка выбора города
    function initCitySelector() {
        const cityInput = document.getElementById('city-selector-input');
        const cityIdsInput = document.getElementById('city-ids-input');
        const selectedCitiesList = document.getElementById('selected-cities-list');
        
        if (!cityInput || !cityIdsInput || !selectedCitiesList) return;
        
        // Обработка ввода с запятыми - после запятой ищем следующий город
        cityInput.addEventListener('input', (e) => {
            const value = e.target.value;
            // Если есть запятая, берем только текст после последней запятой
            const parts = value.split(',');
            const lastPart = parts[parts.length - 1].trim();
            
            // Если после запятой есть текст, обновляем значение для поиска
            if (parts.length > 1 && lastPart) {
                // Оставляем только последнюю часть для поиска
                // Но сохраняем все предыдущие части в поле
                // Автокомплит будет искать по последней части
            }
        });
        
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
            
            const hiddenInput = cityInput.closest('.vp-city-autocomplete')?.querySelector('.vp-city-id');
            if (hiddenInput) hiddenInput.value = '';
            
            const dropdown = cityInput.closest('.vp-city-autocomplete')?.querySelector('.vp-city-autocomplete-dropdown');
            if (dropdown) dropdown.classList.remove('show');
            
            // Фокусируемся на поле, чтобы можно было продолжить ввод
            setTimeout(() => {
                cityInput.focus();
                // Устанавливаем курсор в конец
                const len = cityInput.value.length;
                cityInput.setSelectionRange(len, len);
            }, 10);
            
            return true;
        }
        
        // Перехватываем события на уровне dropdown
        // Используем setInterval для постоянного переопределения обработчиков после их создания
        let handlerSetupInterval = null;
        
        const setupHandlers = () => {
            const dropdown = cityInput.closest('.vp-city-autocomplete')?.querySelector('.vp-city-autocomplete-dropdown');
            if (!dropdown) return;
            
            // Находим все элементы списка и переопределяем их обработчики
            const items = dropdown.querySelectorAll('.vp-city-autocomplete-item');
            items.forEach(item => {
                // Проверяем, не обработан ли уже этот элемент
                if (item.dataset.customHandler === 'true') return;
                
                // Клонируем элемент, чтобы удалить все старые обработчики
                const newItem = item.cloneNode(true);
                newItem.dataset.customHandler = 'true';
                item.parentNode.replaceChild(newItem, item);
                
                // Добавляем наш обработчик с максимальным приоритетом
                newItem.addEventListener('click', (e) => {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    
                    const cityId = parseInt(newItem.dataset.cityId);
                    const cityName = newItem.dataset.cityName || newItem.textContent.trim();
                    
                    if (cityId && handleCitySelection(cityId, cityName)) {
                        return false;
                    }
                }, true); // capture phase - выполнится первым
            });
        };
        
        // Настраиваем обработчики при открытии dropdown
        const observer = new MutationObserver(() => {
            setupHandlers();
        });
        
        setTimeout(() => {
            const dropdown = cityInput.closest('.vp-city-autocomplete')?.querySelector('.vp-city-autocomplete-dropdown');
            if (dropdown) {
                observer.observe(dropdown, {
                    childList: true,
                    subtree: true
                });
                
                // Также настраиваем при каждом открытии dropdown
                const checkAndSetup = () => {
                    if (dropdown.classList.contains('show')) {
                        setTimeout(setupHandlers, 10);
                    }
                };
                
                cityInput.addEventListener('focus', checkAndSetup);
                cityInput.addEventListener('input', checkAndSetup);
                
                // Периодически проверяем и переопределяем обработчики
                handlerSetupInterval = setInterval(() => {
                    if (dropdown.classList.contains('show')) {
                        setupHandlers();
                    }
                }, 100);
            }
        }, 500);
        
        // Очищаем interval при размонтировании
        window.addEventListener('beforeunload', () => {
            if (handlerSetupInterval) {
                clearInterval(handlerSetupInterval);
            }
        });
    }
    
    // Инициализируем при загрузке и при изменении роли
    if (roleSelect) {
        roleSelect.addEventListener('change', () => {
            setTimeout(initCitySelector, 100);
            toggleCitySelector(); // Обновляем видимость кнопок
        });
    }
    initCitySelector();
    
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
                e.preventDefault();
                e.stopPropagation();
                const cityId = parseInt(btn.dataset.cityId);
                selectedCities = selectedCities.filter(c => c.id !== cityId);
                updateSelectedCitiesList();
            });
        });
    }
</script>
@endpush
@endsection
