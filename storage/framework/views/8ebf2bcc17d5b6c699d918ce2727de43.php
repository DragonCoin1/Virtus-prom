
<?php $__env->startSection('title', 'Промоутеры'); ?>

<?php
    $statusMap = [
        'active' => 'Активен',
        'trainee' => 'Стажёр',
        'paused' => 'Пауза',
        'fired' => 'Уволен',
    ];
?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Промоутеры</h3>
    <div class="d-flex gap-2">
        <a class="btn btn-primary btn-sm" href="<?php echo e(route('promoters.create')); ?>">+ Добавить</a>
    </div>
</div>

<?php if(session('ok')): ?>
    <div class="alert alert-success"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="GET" action="<?php echo e(route('module.promoters')); ?>">
    <div class="col-md-6">
        <input class="form-control" name="q" placeholder="Поиск: имя или телефон" value="<?php echo e(request('q')); ?>">
    </div>
    <div class="col-md-4">
        <select class="form-select" name="status">
            <option value="">Статус: все</option>
            <option value="active" <?php if(request('status')==='active'): echo 'selected'; endif; ?>>Активен</option>
            <option value="trainee" <?php if(request('status')==='trainee'): echo 'selected'; endif; ?>>Стажёр</option>
            <option value="paused" <?php if(request('status')==='paused'): echo 'selected'; endif; ?>>Пауза</option>
            <option value="fired" <?php if(request('status')==='fired'): echo 'selected'; endif; ?>>Уволен</option>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-outline-primary w-100">Фильтр</button>
        <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('module.promoters')); ?>">Сброс</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Статус</th>
                <th>Найм</th>
                <th>Увольнение</th>
                <th>Комментарий</th>
                <th style="width: 80px;"></th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $promoters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($p->promoter_full_name); ?></td>
                    <td><?php echo e($p->promoter_phone); ?></td>
                    <td>
                        <span class="badge bg-secondary">
                            <?php echo e($statusMap[$p->promoter_status] ?? $p->promoter_status); ?>

                        </span>
                    </td>
                    <td><?php echo e($p->hired_at); ?></td>
                    <td><?php echo e($p->fired_at); ?></td>
                    <td><?php echo e($p->promoter_comment); ?></td>

                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    title="Действия">
                                ⋮
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo e(route('promoters.edit', $p)); ?>">
                                        Править
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="<?php echo e(route('promoters.destroy', $p)); ?>"
                                          onsubmit="return confirm('Удалить промоутера: <?php echo e($p->promoter_full_name); ?> ?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button class="dropdown-item text-danger" type="submit">
                                            Удалить
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($promoters->count() === 0): ?>
                <tr><td colspan="7" class="text-center text-muted p-4">Пока нет промоутеров</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?php echo e($promoters->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/promoters/index.blade.php ENDPATH**/ ?>