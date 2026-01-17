
<?php $__env->startSection('title', 'Разноска'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Разноска</h3>
    <?php if(!empty($canEdit)): ?>
        <a class="btn btn-primary btn-sm" href="<?php echo e(route('route_actions.create')); ?>">+ Добавить</a>
    <?php endif; ?>
</div>

<?php if(session('ok')): ?>
    <div class="alert alert-success"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<?php
    $templatesText = function($templates) {
        if (!$templates || $templates->count() === 0) return '—';
        return $templates->pluck('template_name')->implode(', ');
    };

    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $promoterId = request('promoter_id');
    $routeId = request('route_id');
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('module.route_actions')); ?>" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo e($dateFrom); ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo e($dateTo); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Промоутер</label>
                <select class="form-select" name="promoter_id">
                    <option value="">— все —</option>
                    <?php $__currentLoopData = $promoters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->promoter_id); ?>" <?php if((string)$promoterId === (string)$p->promoter_id): echo 'selected'; endif; ?>>
                            <?php echo e($p->promoter_full_name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Маршрут</label>
                <select class="form-select" name="route_id">
                    <option value="">— все —</option>
                    <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($r->route_id); ?>" <?php if((string)$routeId === (string)$r->route_id): echo 'selected'; endif; ?>>
                            <?php echo e($r->route_code); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('module.route_actions')); ?>">Сброс</a>
            </div>
        </form>
    </div>

    <?php if(!empty($hasFilters)): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Итого оплата: <strong><?php echo e((int)($sumPayment ?? 0)); ?></strong>
            </div>
            <div class="text-muted">
                Записей: <strong><?php echo e($actions->total()); ?></strong>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>Дата</th>
                <th>Промоутер</th>
                <th>Маршрут</th>
                <th>Макеты</th>
                <th>Листовки</th>
                <th>Расклейка</th>
                <th>Визитки</th>
                <th>Ящики</th>
                <th>Оплата</th>
                <th>Комментарий</th>
                <th>Внёс</th>
                <?php if(!empty($canEdit)): ?>
                    <th style="width: 80px;"></th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($a->action_date); ?></td>
                    <td><?php echo e($a->promoter?->promoter_full_name ?? ('ID ' . $a->promoter_id)); ?></td>
                    <td><?php echo e($a->route?->route_code ?? ('ID ' . $a->route_id)); ?></td>
                    <td><?php echo e($templatesText($a->templates)); ?></td>
                    <td><?php echo e($a->leaflets_total); ?></td>
                    <td><?php echo e($a->posters_total); ?></td>
                    <td><?php echo e($a->cards_count); ?></td>
                    <td><?php echo e($a->boxes_done); ?></td>
                    <td><?php echo e($a->payment_amount ?? 0); ?></td>
                    <td><?php echo e($a->action_comment); ?></td>
                    <td><?php echo e($a->createdBy?->user_login ?? '—'); ?></td>

                    <?php if(!empty($canEdit)): ?>
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
                                        <a class="dropdown-item" href="<?php echo e(route('route_actions.edit', $a)); ?>">
                                            Править
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST"
                                              action="<?php echo e(route('route_actions.destroy', $a)); ?>"
                                              onsubmit="return confirm('Удалить запись разноски?');">
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
                    <?php endif; ?>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($actions->count() === 0): ?>
                <tr>
                    <td colspan="<?php echo e(!empty($canEdit) ? 12 : 11); ?>" class="text-center text-muted p-4">
                        Пока нет записей разноски
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?php echo e($actions->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/route_actions/index.blade.php ENDPATH**/ ?>