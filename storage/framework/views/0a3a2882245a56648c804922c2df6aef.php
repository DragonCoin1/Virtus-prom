
<?php $__env->startSection('title', 'Зарплата'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $promoterId = request('promoter_id');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Зарплата</h3>
    <?php if(!empty($canEdit)): ?>
        <a class="btn btn-primary btn-sm" href="<?php echo e(route('salary.adjustments.create')); ?>">+ Корректировка</a>
    <?php endif; ?>
</div>

<?php if(session('ok')): ?>
    <div class="alert alert-success"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="<?php echo e(route('salary.index')); ?>">
            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo e($dateFrom); ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo e($dateTo); ?>">
            </div>

            <div class="col-md-4">
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

            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('salary.index')); ?>">Сброс</a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th>Промоутер</th>
                <th style="width: 160px;">По разноске</th>
                <th style="width: 160px;">Корректировки</th>
                <th style="width: 160px;">Итого</th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="fw-semibold"><?php echo e($r['promoter_name']); ?></td>
                    <td><?php echo e($r['sum_payment']); ?></td>
                    <td><?php echo e($r['sum_adj']); ?></td>
                    <td class="fw-semibold"><?php echo e($r['sum_final']); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if(count($rows) === 0): ?>
                <tr><td colspan="4" class="text-center text-muted p-4">Нет данных за выбранный период</td></tr>
            <?php endif; ?>
            </tbody>

            <?php if(count($rows) > 0): ?>
                <tfoot>
                <tr>
                    <th>Итого</th>
                    <th><?php echo e($totalPayment); ?></th>
                    <th><?php echo e($totalAdj); ?></th>
                    <th><?php echo e($totalFinal); ?></th>
                </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Последние корректировки
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Дата</th>
                <th>Промоутер</th>
                <th style="width: 140px;">Сумма</th>
                <th>Комментарий</th>
                <th style="width: 160px;">Внёс</th>
                <th style="width: 90px;"></th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $lastAdjustments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($a->adj_date); ?></td>
                    <td><?php echo e($a->promoter?->promoter_full_name ?? ('ID ' . $a->promoter_id)); ?></td>
                    <td class="<?php echo e((int)$a->amount < 0 ? 'text-danger' : 'text-success'); ?>">
                        <?php echo e($a->amount); ?>

                    </td>
                    <td><?php echo e($a->comment ?? '—'); ?></td>
                    <td><?php echo e($a->createdBy?->user_login ?? '—'); ?></td>
                    <td class="text-end">
                        <?php if(!empty($canEdit)): ?>
                            <form method="POST"
                                  action="<?php echo e(route('salary.adjustments.destroy', $a)); ?>"
                                  onsubmit="return confirm('Удалить корректировку?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="btn btn-sm btn-outline-danger">×</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($lastAdjustments->count() === 0): ?>
                <tr><td colspan="6" class="text-center text-muted p-4">Пока нет корректировок</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/salary/index.blade.php ENDPATH**/ ?>