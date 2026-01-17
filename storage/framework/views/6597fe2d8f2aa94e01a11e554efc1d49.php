
<?php $__env->startSection('title', 'Отчёты'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $sortLabel = $sort === 'asc' ? 'Старые → новые' : 'Новые → старые';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Отчёты</h3>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>Сводка за текущий месяц</div>
        <div class="text-muted" style="font-size: 12px;">
            <?php echo e($month['from']); ?> — <?php echo e($month['to']); ?>

        </div>
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <div class="text-muted">Листовки</div>
                <div class="fs-5 fw-semibold"><?php echo e($month['leaflets']); ?></div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Ящики</div>
                <div class="fs-5 fw-semibold"><?php echo e($month['boxes']); ?></div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Расклейка</div>
                <div class="fs-5 fw-semibold"><?php echo e($month['posters']); ?></div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Визитки</div>
                <div class="fs-5 fw-semibold"><?php echo e($month['cards']); ?></div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Оплата</div>
                <div class="fs-5 fw-semibold"><?php echo e($month['payment']); ?></div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Цена ящика</div>
                <div class="fs-5 fw-semibold"><?php echo e($priceBox === null ? '—' : $priceBox); ?></div>
            </div>

            <div class="col-md-2">
                <div class="text-muted">Цена листовки</div>
                <div class="fs-5 fw-semibold"><?php echo e($priceLeaflet === null ? '—' : $priceLeaflet); ?></div>
            </div>

            <div class="col-md-10">
                <div class="text-muted mb-1">Выдано за месяц</div>
                <div class="d-flex gap-3 flex-wrap">
                    <div>Листовки: <strong><?php echo e($month['leaflets_issued']); ?></strong></div>
                    <div>Расклейка: <strong><?php echo e($month['posters_issued']); ?></strong></div>
                    <div>Визитки: <strong><?php echo e($month['cards_issued']); ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="<?php echo e(route('reports.index')); ?>">
            <div class="col-md-3">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo e($dateFrom); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo e($dateTo); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Сортировка</label>
                <select class="form-select" name="sort">
                    <option value="desc" <?php if($sort==='desc'): echo 'selected'; endif; ?>>Новые → старые</option>
                    <option value="asc" <?php if($sort==='asc'): echo 'selected'; endif; ?>>Старые → новые</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('reports.index')); ?>">Сброс</a>
            </div>

            <div class="col-md-12 text-muted" style="font-size: 12px;">
                Сейчас: <?php echo e($dateFrom); ?> — <?php echo e($dateTo); ?> • <?php echo e($sortLabel); ?>

            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Дата</th>

                <th style="width: 120px;">Листовки</th>
                <th style="width: 120px;">Ящики</th>
                <th style="width: 120px;">Расклейка</th>
                <th style="width: 120px;">Визитки</th>
                <th style="width: 140px;">Оплата</th>

                <th style="width: 140px;">Выдано листовок</th>
                <th style="width: 140px;">Выдано расклеек</th>
                <th style="width: 140px;">Выдано визиток</th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $daily; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="fw-semibold"><?php echo e($d->action_date); ?></td>

                    <td><?php echo e((int)$d->sum_leaflets); ?></td>
                    <td><?php echo e((int)$d->sum_boxes); ?></td>
                    <td><?php echo e((int)$d->sum_posters); ?></td>
                    <td><?php echo e((int)$d->sum_cards); ?></td>
                    <td class="fw-semibold"><?php echo e((int)$d->sum_payment); ?></td>

                    <td><?php echo e((int)$d->sum_leaflets_issued); ?></td>
                    <td><?php echo e((int)$d->sum_posters_issued); ?></td>
                    <td><?php echo e((int)$d->sum_cards_issued); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if(count($daily) === 0): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted p-4">Нет данных за выбранный период</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/reports/index.blade.php ENDPATH**/ ?>