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

<div class="container-fluid vp-shell">
    <div class="row min-vh-100">

        <aside class="col-12 col-md-3 col-lg-2 p-0 vp-sidebar d-flex flex-column">
            <div class="vp-brand">
                <div class="vp-brand-title">Virtus Prom</div>
                <div class="vp-brand-sub">
                    @if(auth()->check())
                        {{ auth()->user()->user_full_name }} ({{ auth()->user()->user_login }})
                    @endif
                </div>
            </div>

            <nav class="vp-nav">
                <a class="vp-nav-link {{ request()->routeIs('module.promoters') ? 'active' : '' }}" href="{{ route('module.promoters') }}">Промоутеры</a>

                <a class="vp-nav-link {{ request()->routeIs('module.route_actions') || request()->routeIs('route_actions.*') ? 'active' : '' }}"
                   href="{{ route('module.route_actions') }}">Разноска</a>

                <a class="vp-nav-link {{ request()->routeIs('module.cards') ? 'active' : '' }}" href="{{ route('module.cards') }}">Карты</a>

                <a class="vp-nav-link {{ request()->routeIs('interviews.*') ? 'active' : '' }}" href="{{ route('interviews.index') }}">Собеседования</a>

                <a class="vp-nav-link {{ request()->routeIs('salary.*') ? 'active' : '' }}" href="{{ route('salary.index') }}">Зарплата</a>

                <a class="vp-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Отчёты</a>
            </nav>

            <div class="vp-sidebar-footer mt-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary w-100">Выйти</button>
                </form>
            </div>
        </aside>

        <main class="col-12 col-md-9 col-lg-10 vp-main">
            @yield('content')
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
