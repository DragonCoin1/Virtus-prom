
<?php $__env->startSection('title', 'Маршруты'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Маршруты</h3>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('routes.import.form')); ?>">Импорт</a>
        <a class="btn btn-primary btn-sm" href="<?php echo e(route('routes.create')); ?>">+ Добавить</a>
    </div>
</div>

<?php if(session('ok')): ?>
    <div class="alert alert-success"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<?php
    $search = request('search');
    $type = request('type');

    $hasArea = \Illuminate\Support\Facades\Schema::hasColumn('routes', 'route_area');
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('routes.index')); ?>" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Поиск</label>
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Код или район">
            </div>

            <div class="col-md-3">
                <label class="form-label">Тип</label>
                <select class="form-select" name="type">
                    <option value="">— все —</option>
                    <option value="city" <?php if($type==='city'): echo 'selected'; endif; ?>>Город</option>
                    <option value="private" <?php if($type==='private'): echo 'selected'; endif; ?>>Частный сектор</option>
                    <option value="mixed" <?php if($type==='mixed'): echo 'selected'; endif; ?>>Смешанный</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('routes.index')); ?>">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Код</th>
                <?php if($hasArea): ?>
                    <th>Район</th>
                <?php endif; ?>
                <th style="width: 160px;">Тип</th>
                <th style="width: 90px;"></th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="fw-semibold"><?php echo e($r->route_code); ?></td>
                    <?php if($hasArea): ?>
                        <td><?php echo e($r->route_area); ?></td>
                    <?php endif; ?>
                    <td>
                        <?php
                            $map = ['city'=>'Город','private'=>'Частный сектор','mixed'=>'Смешанный'];
                        ?>
                        <?php echo e($map[$r->route_type] ?? $r->route_type); ?>

                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('routes.edit', $r)); ?>">Править</a>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($routes->count() === 0): ?>
                <tr>
                    <td colspan="<?php echo e($hasArea ? 4 : 3); ?>" class="text-center text-muted p-4">
                        Нет маршрутов
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?php echo e($routes->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/routes/index.blade.php ENDPATH**/ ?>