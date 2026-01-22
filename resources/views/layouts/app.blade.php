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

                $canManageUsers = !$accessService->isPromoter($user);
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
</body>
</html>
