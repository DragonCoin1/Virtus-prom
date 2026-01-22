<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Virtus Prom')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('css/vp.css') }}" rel="stylesheet">
</head>
<body class="vp-body">

<div class="vp-shell">
    @php
        use Illuminate\Support\Facades\DB;
        use App\Services\AccessService;

        $canViewModules = [];
        $canEditModules = [];
        $canManageUsers = false;

        if (auth()->check()) {
            $user = auth()->user();
            $accessService = app(AccessService::class);
            
            // Для developer - все права автоматически
            if ($accessService->isDeveloper($user)) {
                $allModules = ['promoters', 'routes', 'route_actions', 'cards', 'interviews', 'salary', 'keys_registry', 'reports', 'ad_templates', 'ad_residuals', 'instructions'];
                foreach ($allModules as $m) {
                    $canViewModules[$m] = true;
                    $canEditModules[$m] = true;
                }
                $canManageUsers = true;
            } else {
                // Для остальных - из БД
                $roleAccess = DB::table('role_module_access')
                    ->where('role_id', $user->role_id)
                    ->get();

                foreach ($roleAccess as $accessRow) {
                    $canViewModules[$accessRow->module_code] = (int) $accessRow->can_view === 1;
                    $canEditModules[$accessRow->module_code] = (int) $accessRow->can_edit === 1;
                }

                // Менеджер не может управлять пользователями
                $canManageUsers = !$accessService->isPromoter($user) && !$accessService->isManager($user);
            }
        }
    @endphp
    <header class="vp-header">
        <div class="container-fluid vp-header-inner">
            <div class="vp-brand-inline">
                <div class="vp-brand-title">Virtus Prom</div>
                <div class="vp-brand-sub">
                    @if(auth()->check())
                        {{ auth()->user()->user_full_name }} ({{ auth()->user()->user_login }})
                    @endif
                </div>
            </div>

            <nav class="vp-nav vp-nav-horizontal">
                @if(!empty($canViewModules['promoters']))
                    <a class="vp-nav-link {{ request()->routeIs('module.promoters') ? 'active' : '' }}" href="{{ route('module.promoters') }}">Промоутеры</a>
                @endif

                @if(!empty($canViewModules['route_actions']))
                    <a class="vp-nav-link {{ request()->routeIs('module.route_actions') || request()->routeIs('route_actions.*') ? 'active' : '' }}"
                       href="{{ route('module.route_actions') }}">Разноска</a>
                @endif

                @if(!empty($canViewModules['cards']))
                    <a class="vp-nav-link {{ request()->routeIs('module.cards') ? 'active' : '' }}" href="{{ route('module.cards') }}">Карты</a>
                @endif

                @if(!empty($canViewModules['interviews']))
                    <a class="vp-nav-link {{ request()->routeIs('interviews.*') ? 'active' : '' }}" href="{{ route('interviews.index') }}">Собеседования</a>
                @endif

                @if(!empty($canViewModules['salary']))
                    <a class="vp-nav-link {{ request()->routeIs('salary.*') ? 'active' : '' }}" href="{{ route('salary.index') }}">Зарплата</a>
                @endif

                @if(!empty($canViewModules['reports']))
                    <a class="vp-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Отчёты</a>
                @endif

                @if(!empty($canViewModules['ad_residuals']))
                    <a class="vp-nav-link {{ request()->routeIs('ad_residuals.*') ? 'active' : '' }}" href="{{ route('ad_residuals.index') }}">Остатки</a>
                @endif

                {{-- Инструкции скрыты по требованию --}}
                {{-- @if(!empty($canViewModules['instructions']))
                    <a class="vp-nav-link {{ request()->routeIs('instructions.*') ? 'active' : '' }}" href="{{ route('instructions.index') }}">Инструкции</a>
                @endif --}}

                @if($canManageUsers || (auth()->check() && app(AccessService::class)->isDeveloper(auth()->user())))
                    <a class="vp-nav-link {{ request()->routeIs('cities.*') ? 'active' : '' }}" href="{{ route('cities.index') }}">Города</a>
                @endif

                @if($canManageUsers)
                    <a class="vp-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Пользователи</a>
                @endif
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="vp-logout">
                @csrf
                <button class="btn btn-outline-secondary">Выйти</button>
            </form>
        </div>
    </header>

    <main class="vp-main">
        <div class="vp-page">
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')

<script>
    // Скрипт для поиска по городам и другим полям с datalist
    document.querySelectorAll('[data-searchable-select]').forEach((input) => {
        const targetId = input.getAttribute('data-hidden-target');
        const hiddenInput = document.getElementById(targetId);
        const listId = input.getAttribute('list');
        const dataList = listId ? document.getElementById(listId) : null;
        if (!hiddenInput || !dataList) {
            return;
        }

        const options = Array.from(dataList.options);

        const syncHidden = () => {
            const value = input.value.trim().toLowerCase();
            const match = options.find((option) => option.value.toLowerCase() === value);
            hiddenInput.value = match ? match.dataset.id : '';
        };

        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(syncHidden, 200);
        });

        input.addEventListener('change', syncHidden);
    });

    // City autocomplete
    document.querySelectorAll('.vp-city-autocomplete').forEach((container) => {
        const input = container.querySelector('.vp-city-input');
        const hiddenInput = container.querySelector('.vp-city-id');
        const dropdown = container.querySelector('.vp-city-autocomplete-dropdown');
        
        if (!input || !hiddenInput || !dropdown) {
            return;
        }
        
        const citiesDataStr = input.getAttribute('data-cities');
        if (!citiesDataStr) {
            return;
        }
        
        let citiesData = [];
        try {
            citiesData = JSON.parse(citiesDataStr);
        } catch (e) {
            console.error('Ошибка парсинга данных городов:', e);
            return;
        }
        
        let highlightedIndex = -1;
        let isDropdownOpen = false;

        const updateDropdownPosition = () => {
            if (!isDropdownOpen) return;
            
            const rect = input.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            
            // Позиционируем сразу под input, без отступа
            dropdown.style.top = (rect.bottom + scrollTop) + 'px';
            dropdown.style.left = (rect.left + scrollLeft) + 'px';
            dropdown.style.width = Math.max(rect.width, 200) + 'px';
        };

        const renderDropdown = (filteredCities) => {
            dropdown.innerHTML = '';
            
            // Добавляем опцию "Все города" в начало
            const allCitiesItem = document.createElement('div');
            allCitiesItem.className = 'vp-city-autocomplete-item';
            if (!hiddenInput.value || hiddenInput.value === '') {
                allCitiesItem.classList.add('selected');
            }
            allCitiesItem.textContent = 'Все города';
            allCitiesItem.dataset.cityId = '';
            allCitiesItem.dataset.cityName = '';
            
            allCitiesItem.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            
            allCitiesItem.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                input.value = '';
                hiddenInput.value = '';
                closeDropdown();
                const form = input.closest('form');
                if (form && form.id === 'cardsCityFilter') {
                    setTimeout(() => form.submit(), 10);
                }
            });

            allCitiesItem.addEventListener('mouseenter', () => {
                highlightedIndex = -1;
                updateHighlight();
            });

            dropdown.appendChild(allCitiesItem);

            if (filteredCities.length === 0) {
                showDropdown();
                return;
            }

            filteredCities.forEach((city, index) => {
                const item = document.createElement('div');
                item.className = 'vp-city-autocomplete-item';
                if (city.id == hiddenInput.value) {
                    item.classList.add('selected');
                }
                item.textContent = city.name;
                item.dataset.cityId = city.id;
                item.dataset.cityName = city.name;
                
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
                
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    input.value = city.name;
                    hiddenInput.value = city.id;
                    closeDropdown();
                    const form = input.closest('form');
                    if (form && form.id === 'cardsCityFilter') {
                        setTimeout(() => form.submit(), 10);
                    }
                });

                item.addEventListener('mouseenter', () => {
                    highlightedIndex = index + 1; // +1 потому что первый элемент "Все города"
                    updateHighlight();
                });

                dropdown.appendChild(item);
            });

            showDropdown();
            highlightedIndex = -1;
        };
        
        const showDropdown = () => {
            isDropdownOpen = true;
            dropdown.classList.add('show');
            updateDropdownPosition();
        };
        
        const closeDropdown = () => {
            isDropdownOpen = false;
            dropdown.classList.remove('show');
            highlightedIndex = -1;
        };

        const updateHighlight = () => {
            const items = dropdown.querySelectorAll('.vp-city-autocomplete-item');
            items.forEach((item, index) => {
                // highlightedIndex = -1 для "Все города", 0+ для остальных
                const isHighlighted = highlightedIndex === -1 ? index === 0 : index === highlightedIndex;
                item.classList.toggle('highlighted', isHighlighted);
            });
        };

        const filterCities = (query) => {
            if (!query.trim()) {
                return citiesData;
            }
            const lowerQuery = query.toLowerCase();
            return citiesData.filter(city => 
                city.name.toLowerCase().includes(lowerQuery)
            );
        };

        input.addEventListener('input', (e) => {
            const query = e.target.value;
            const filtered = filterCities(query);
            updateDropdownPosition();
            renderDropdown(filtered);
            
            // Если точное совпадение, устанавливаем ID
            const exactMatch = citiesData.find(c => c.name.toLowerCase() === query.toLowerCase());
            if (exactMatch) {
                hiddenInput.value = exactMatch.id;
            } else {
                hiddenInput.value = '';
            }
        });

        input.addEventListener('focus', () => {
            const query = input.value;
            const filtered = filterCities(query);
            updateDropdownPosition();
            renderDropdown(filtered);
        });

        input.addEventListener('keydown', (e) => {
            const items = dropdown.querySelectorAll('.vp-city-autocomplete-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!isDropdownOpen) {
                    const query = input.value;
                    const filtered = filterCities(query);
                    renderDropdown(filtered);
                }
                if (items.length > 0) {
                    highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
                    updateHighlight();
                    if (highlightedIndex >= 0 && items[highlightedIndex]) {
                        items[highlightedIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length > 0) {
                    highlightedIndex = Math.max(highlightedIndex - 1, -1);
                    updateHighlight();
                    if (highlightedIndex >= 0 && items[highlightedIndex]) {
                        items[highlightedIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (items.length > 0) {
                    if (highlightedIndex >= 0 && items[highlightedIndex]) {
                        items[highlightedIndex].click();
                    } else if (highlightedIndex === -1 && items[0]) {
                        // Если ничего не выделено, выбираем первый элемент (Все города)
                        items[0].click();
                    }
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        // Обновление позиции при скролле
        let scrollTimeout;
        const handleScroll = () => {
            if (isDropdownOpen) {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    updateDropdownPosition();
                }, 10);
            }
        };
        
        window.addEventListener('scroll', handleScroll, true);
        window.addEventListener('resize', () => {
            if (isDropdownOpen) {
                updateDropdownPosition();
            }
        });

        // Закрытие при клике вне
        const handleDocumentClick = (e) => {
            if (!container.contains(e.target) && !dropdown.contains(e.target)) {
                closeDropdown();
            }
        };
        
        // Используем capture phase для более надежного закрытия
        document.addEventListener('mousedown', handleDocumentClick, true);
        
        // Очистка при удалении элемента
        const observer = new MutationObserver(() => {
            if (!document.body.contains(container)) {
                window.removeEventListener('scroll', handleScroll, true);
                document.removeEventListener('mousedown', handleDocumentClick, true);
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>
</body>
</html>
