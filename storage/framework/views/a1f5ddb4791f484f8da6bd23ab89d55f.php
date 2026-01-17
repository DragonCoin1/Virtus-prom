
<?php $__env->startSection('title', 'Макеты'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Макеты</h3>
    <a class="btn btn-primary btn-sm" href="<?php echo e(route('ad_templates.create')); ?>">+ Добавить</a>
</div>

<?php if(session('ok')): ?>
    <div class="alert alert-success"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<?php
    $status = request('status');
    $type = request('type');
    $search = request('search');
?>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="<?php echo e(route('ad_templates.index')); ?>">
            <div class="col-md-3">
                <label class="form-label">Поиск</label>
                <input class="form-control" name="search" value="<?php echo e($search); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Тип</label>
                <select class="form-select" name="type">
                    <option value="">— все —</option>
                    <option value="leaflet" <?php if($type==='leaflet'): echo 'selected'; endif; ?>>Листовка</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select class="form-select" name="status">
                    <option value="">— все —</option>
                    <option value="active" <?php if($status==='active'): echo 'selected'; endif; ?>>Активен</option>
                    <option value="inactive" <?php if($status==='inactive'): echo 'selected'; endif; ?>>Выключен</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('ad_templates.index')); ?>">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>Название</th>
                <th>Тип</th>
                <th>Статус</th>
                <th style="width: 120px;"></th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($t->template_name); ?></td>
                    <td><?php echo e($t->template_type === 'leaflet' ? 'Листовка' : $t->template_type); ?></td>
                    <td>
                        <?php if($t->is_active): ?>
                            <span class="badge text-bg-success">Активен</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Выключен</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ⋮
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo e(route('ad_templates.edit', $t)); ?>">Править</a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="<?php echo e(route('ad_templates.toggle', $t)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button class="dropdown-item" type="submit">
                                            <?php echo e($t->is_active ? 'Выключить' : 'Включить'); ?>

                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($templates->count() === 0): ?>
                <tr><td colspan="4" class="text-center text-muted p-4">Пока нет макетов</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?php echo e($templates->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/ad_templates/index.blade.php ENDPATH**/ ?>