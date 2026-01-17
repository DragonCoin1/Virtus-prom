<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Virtus Prom'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="<?php echo e(asset('css/vp.css')); ?>" rel="stylesheet">
</head>
<body class="vp-body">

<div class="container-fluid vp-shell">
    <div class="row min-vh-100">

        <aside class="col-12 col-md-3 col-lg-2 p-0 vp-sidebar d-flex flex-column">
            <div class="vp-brand">
                <div class="vp-brand-title">Virtus Prom</div>
                <div class="vp-brand-sub">
                    <?php if(auth()->check()): ?>
                        <?php echo e(auth()->user()->user_full_name); ?> (<?php echo e(auth()->user()->user_login); ?>)
                    <?php endif; ?>
                </div>
            </div>

            <nav class="vp-nav">
                <a class="vp-nav-link <?php echo e(request()->routeIs('home') ? 'active' : ''); ?>" href="<?php echo e(route('home')); ?>">Главная</a>

                <a class="vp-nav-link <?php echo e(request()->routeIs('module.promoters') ? 'active' : ''); ?>" href="<?php echo e(route('module.promoters')); ?>">Промоутеры</a>

                <a class="vp-nav-link <?php echo e(request()->routeIs('routes.*') ? 'active' : ''); ?>" href="<?php echo e(route('routes.index')); ?>">Маршруты</a>

                <a class="vp-nav-link <?php echo e(request()->routeIs('module.route_actions') || request()->routeIs('route_actions.*') ? 'active' : ''); ?>"
                   href="<?php echo e(route('module.route_actions')); ?>">Разноска</a>

                <a class="vp-nav-link <?php echo e(request()->routeIs('module.cards') ? 'active' : ''); ?>" href="<?php echo e(route('module.cards')); ?>">Карты</a>

                <a class="vp-nav-link <?php echo e(request()->routeIs('interviews.*') ? 'active' : ''); ?>" href="<?php echo e(route('interviews.index')); ?>">Собеседования</a>

                <a class="vp-nav-link <?php echo e(request()->routeIs('salary.*') ? 'active' : ''); ?>" href="<?php echo e(route('salary.index')); ?>">Зарплата</a>

                <a class="vp-nav-link <?php echo e(request()->routeIs('reports.*') ? 'active' : ''); ?>" href="<?php echo e(route('reports.index')); ?>">Отчёты</a>
            </nav>

            <div class="vp-sidebar-footer mt-auto">
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-outline-secondary w-100">Выйти</button>
                </form>
            </div>
        </aside>

        <main class="col-12 col-md-9 col-lg-10 vp-main">
            <?php echo $__env->yieldContent('content'); ?>
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php /**PATH C:\laragon\www\virtus-prom\resources\views/layouts/app.blade.php ENDPATH**/ ?>