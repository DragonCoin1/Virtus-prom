
<?php $__env->startSection('title', 'Карты'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Carbon\Carbon;

    $now = Carbon::now();
    $limitDays = 7;

    $typeMap = [
        'city' => 'Город',
        'private' => 'Частный сектор',
        'mixed' => 'Смешанный',
    ];

    $fmtAge = function (?Carbon $last, Carbon $now) {
        if (!$last) return '—';

        $hours = $last->diffInHours($now);
        if ($hours < 48) {
            return (int)$hours . ' ч';
        }

        $days = $last->diffInDays($now);
        if ($days < 60) {
            return (int)$days . ' д';
        }

        $months = $last->diffInMonths($now);
        if ($months < 24) {
            return (int)$months . ' мес';
        }

        $years = $last->diffInYears($now);
        return (int)$years . ' г';
    };
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Карты</h3>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary btn-sm" href="<?php echo e(route('ad_templates.index')); ?>">Макеты</a>
    </div>
</div>

<div class="card">
    <div class="card-body py-2">
        <div class="text-muted">
            Статус: <span class="vp-dot vp-dot-green"></span> менее или равно <?php echo e($limitDays); ?> дней,
            <span class="vp-dot vp-dot-purple"></span> более <?php echo e($limitDays); ?> дней / нет прохождений
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="sticky-top" style="background:#fff;">
            <tr>
                <th style="width: 56px;">Статус</th>
                <th style="width: 120px;">Код</th>
                <th>Район</th>
                <th style="width: 140px;">Тип</th>
                <th style="width: 160px;">Последнее прохождение</th>
                <th style="width: 120px;">Давность</th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $last = $r->last_action_date ? Carbon::parse($r->last_action_date) : null;
                    $days = $last ? $last->diffInDays($now) : null;

                    $isPurple = !$last || $days > $limitDays;

                    // район — если у тебя колонка называется иначе, просто поменяешь тут ключ
                    $area = $r->route_area ?? $r->route_district ?? $r->route_region ?? '—';

                    $rawType = $r->route_type ?? null;
                    $typeText = $rawType !== null ? ($typeMap[$rawType] ?? $rawType) : '—';
                ?>

                <tr>
                    <td><span class="vp-dot <?php echo e($isPurple ? 'vp-dot-purple' : 'vp-dot-green'); ?>"></span></td>
                    <td class="fw-semibold"><?php echo e($r->route_code ?? ('ID ' . ($r->route_id ?? ''))); ?></td>
                    <td><?php echo e($area); ?></td>
                    <td><?php echo e($typeText); ?></td>
                    <td><?php echo e($last ? $last->format('Y-m-d') : '—'); ?></td>
                    <td><?php echo e($fmtAge($last, $now)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($routes->count() === 0): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted p-4">Нет маршрутов</td>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/modules/cards.blade.php ENDPATH**/ ?>