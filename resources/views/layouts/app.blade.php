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
            </div>

            <nav class="vp-nav vp-nav-horizontal">
                @if(!empty($canViewModules['promoters']))
                    <a class="vp-nav-link {{ request()->routeIs('module.promoters') ? 'active' : '' }}" href="{{ route('module.promoters') }}">Промоутеры</a>
                @endif
                @if(!empty($canViewModules['route_actions']))
                    <a class="vp-nav-link {{ request()->routeIs('module.route_actions') || request()->routeIs('route_actions.*') ? 'active' : '' }}" href="{{ route('module.route_actions') }}">Разноска</a>
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
                @php
                    $currentUser = auth()->user();
                    $accessService = app(AccessService::class);
                    $showCitiesLink = false;
                    if ($currentUser) {
                        // Директор не должен видеть вкладку "Города"
                        $showCitiesLink = !$accessService->isBranchDirector($currentUser) && (
                                         $canManageUsers || 
                                         $accessService->isDeveloper($currentUser) || 
                                         $accessService->isGeneralDirector($currentUser) || 
                                         $accessService->isRegionalDirector($currentUser));
                    }
                @endphp
                @if($showCitiesLink)
                    <a class="vp-nav-link {{ request()->routeIs('cities.*') ? 'active' : '' }}" href="{{ route('cities.index') }}">Города</a>
                @endif
                @if($canManageUsers)
                    <a class="vp-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Пользователи</a>
                @endif
            </nav>

            <div class="vp-header-right">
                @if(auth()->check())
                    @php
                        $roleCode = $accessService->roleName(auth()->user());
                        $roleLabelMap = [
                            'branch_director' => 'Директор',
                            'general_director' => 'Генеральный директор',
                            'manager' => 'Менеджер',
                            'regional_director' => 'Региональный директор',
                            'promoter' => 'Промоутер',
                            'developer' => 'developer',
                        ];
                        $roleLabel = $roleLabelMap[$roleCode] ?? ($roleCode ?: '');
                    @endphp
                    <div class="vp-account">
                        {{ auth()->user()->user_full_name }}@if($roleLabel) — {{ $roleLabel }}@endif
                    </div>
                @endif

                <form method="POST" action="{{ route('logout') }}" class="vp-logout">
                    @csrf
                    <button class="btn btn-outline-secondary">Выйти</button>
                </form>
            </div>
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
    // Configure Bootstrap dropdowns globally to avoid clipping/flicker near viewport edges (esp. last rows)
    const vpInitDropdowns = () => {
        if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;

        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
            // If instance already exists, keep it
            const existing = bootstrap.Dropdown.getInstance(toggle);
            if (existing) return;

            new bootstrap.Dropdown(toggle, {
                popperConfig: (defaultConfig) => {
                    const cfg = defaultConfig || {};
                    cfg.strategy = 'fixed';
                    cfg.modifiers = (cfg.modifiers || []).filter((m) => m && m.name !== 'preventOverflow' && m.name !== 'flip');
                    cfg.modifiers.push(
                        { name: 'preventOverflow', options: { boundary: 'viewport', padding: 8 } },
                        { name: 'flip', options: { boundary: 'viewport', padding: 8 } }
                    );
                    return cfg;
                },
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', vpInitDropdowns);
    } else {
        vpInitDropdowns();
    }

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

    // Dropdown (⋮ actions) anti-clipping: add a class to ancestors while menu is open
    // This is a fallback for browsers without :has() support and for overflow:auto containers.
    // Before opening a dropdown, force-close any other open dropdowns (prevents stacking/hover flicker)
    document.addEventListener('show.bs.dropdown', (e) => {
        const nextToggle = e.target;
        const nextRoot = nextToggle?.closest('.dropdown') || null;

        document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
            const root = menu.closest('.dropdown');
            if (!root) return;
            if (nextRoot && root === nextRoot) return;

            const toggle = root.querySelector('[data-bs-toggle="dropdown"]');
            if (!toggle) return;

            const inst = bootstrap.Dropdown.getInstance(toggle) || new bootstrap.Dropdown(toggle);
            inst.hide();
        });

        // Clear any leftover helper classes from previous menus
        document.querySelectorAll('.vp-dropdown-open').forEach((el) => el.classList.remove('vp-dropdown-open'));
        document.querySelectorAll('.vp-dropdown-open-row').forEach((el) => el.classList.remove('vp-dropdown-open-row'));
        document.body.classList.remove('vp-dropdown-open');
    });

    document.addEventListener('shown.bs.dropdown', (e) => {
        const toggle = e.target;
        if (!toggle) return;
        const dropdownRoot = toggle.closest('.dropdown') || toggle.parentElement;
        if (!dropdownRoot) return;
        let parent = dropdownRoot;
        while (parent && parent !== document.body) {
            parent.classList.add('vp-dropdown-open');
            parent = parent.parentElement;
        }

        // Mark table row to guarantee correct stacking order
        const tr = dropdownRoot.closest('tr');
        if (tr) tr.classList.add('vp-dropdown-open-row');

        // Also mark body so global CSS can disable hover transforms
        document.body.classList.add('vp-dropdown-open');
    });

    document.addEventListener('hidden.bs.dropdown', () => {
        document.querySelectorAll('.vp-dropdown-open').forEach((el) => el.classList.remove('vp-dropdown-open'));
        document.querySelectorAll('.vp-dropdown-open-row').forEach((el) => el.classList.remove('vp-dropdown-open-row'));
        document.body.classList.remove('vp-dropdown-open');
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
            
            // Позиционируем сразу под input, без отступа
            // Используем rect.bottom для точного позиционирования
            // IMPORTANT: dropdown is position:fixed, so rect.* is already viewport-based.
            // Adding scroll offsets makes the dropdown drift and can overlap the input.
            const topPosition = rect.bottom + 4; // small gap so dropdown never touches/overlaps the input border
            dropdown.style.position = 'fixed';
            dropdown.style.top = topPosition + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.width = Math.max(rect.width, 200) + 'px';
            dropdown.style.marginTop = '0';
            dropdown.style.paddingTop = '0';
            dropdown.style.transform = 'none';
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
                    
                    // Проверяем, является ли это полем для множественного выбора городов
                    const isMultipleCities = input.dataset.multipleCities === 'true';
                    if (isMultipleCities) {
                        // Для множественного выбора не устанавливаем значение напрямую
                        // Логика будет обработана в users/create.blade.php
                        return;
                    }
                    
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
            
            // Portal approach: move dropdown to body to escape any parent transforms/overflow
            // This guarantees position:fixed works correctly regardless of DOM structure
            if (dropdown.parentElement !== document.body) {
                dropdown.dataset.originalParent = container.outerHTML.substring(0, 50); // just for reference
                document.body.appendChild(dropdown);
            }
            
            // Mark body so CSS can disable ALL card transforms on the page (prevents position:fixed issues)
            document.body.classList.add('vp-autocomplete-open');

            // Position and show immediately (no need to wait, dropdown is already in body)
            updateDropdownPosition();
            dropdown.classList.add('show');
        };
        
        const closeDropdown = () => {
            if (isDropdownOpen) {
                isDropdownOpen = false;
                dropdown.classList.remove('show');
                highlightedIndex = -1;
                
                // Return dropdown to original container (portal cleanup)
                if (dropdown.parentElement === document.body && container.parentElement) {
                    container.appendChild(dropdown);
                }
                
                // Убираем класс с родительских элементов
                document.querySelectorAll('.vp-autocomplete-open').forEach(el => {
                    el.classList.remove('vp-autocomplete-open');
                });
                
                // Remove body class only if no other autocomplete is open
                if (!document.querySelector('.vp-city-autocomplete-dropdown.show')) {
                    document.body.classList.remove('vp-autocomplete-open');
                }
            }
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
            // Если есть запятые, берем только текст после последней запятой
            const parts = query.split(',');
            const searchQuery = parts[parts.length - 1].trim();
            if (!searchQuery) {
                return citiesData;
            }
            const lowerQuery = searchQuery.toLowerCase();
            return citiesData.filter(city => 
                city.name.toLowerCase().includes(lowerQuery)
            );
        };

        input.addEventListener('input', (e) => {
            const query = e.target.value;
            // Если есть запятые, берем только текст после последней запятой для поиска
            const parts = query.split(',');
            const searchQuery = parts[parts.length - 1].trim();
            const filtered = filterCities(query);
            updateDropdownPosition();
            renderDropdown(filtered);
            
            // Если точное совпадение, устанавливаем ID
            const exactMatch = citiesData.find(c => c.name.toLowerCase() === searchQuery.toLowerCase());
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
