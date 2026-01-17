
<?php $__env->startSection('title', 'Собеседования'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $status = request('status');
    $search = request('search');
    $dateFrom = request('date_from');
    $dateTo = request('date_to');

    $statusMap = [
        'planned' => ['label' => 'Запланировано', 'badge' => 'text-bg-primary'],
        'came' => ['label' => 'Пришёл', 'badge' => 'text-bg-success'],
        'no_show' => ['label' => 'Не пришёл', 'badge' => 'text-bg-secondary'],
        'hired' => ['label' => 'Принят', 'badge' => 'text-bg-success'],
        'rejected' => ['label' => 'Отказ', 'badge' => 'text-bg-danger'],
    ];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Собеседования</h3>
    <a class="btn btn-primary btn-sm" href="<?php echo e(route('interviews.create')); ?>">+ Добавить</a>
</div>

<?php if(session('ok')): ?>
    <div class="alert alert-success"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="<?php echo e(route('interviews.index')); ?>">
            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo e($dateFrom); ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo e($dateTo); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select class="form-select" name="status">
                    <option value="">— все —</option>
                    <option value="planned" <?php if($status==='planned'): echo 'selected'; endif; ?>>Запланировано</option>
                    <option value="came" <?php if($status==='came'): echo 'selected'; endif; ?>>Пришёл</option>
                    <option value="no_show" <?php if($status==='no_show'): echo 'selected'; endif; ?>>Не пришёл</option>
                    <option value="hired" <?php if($status==='hired'): echo 'selected'; endif; ?>>Принят</option>
                    <option value="rejected" <?php if($status==='rejected'): echo 'selected'; endif; ?>>Отказ</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Поиск</label>
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="ФИО / телефон / источник">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Показать</button>
                <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('interviews.index')); ?>">Сброс</a>
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
                <th>Кандидат</th>
                <th style="width: 160px;">Телефон</th>
                <th style="width: 160px;">Источник</th>
                <th style="width: 170px;">Статус</th>
                <th>Комментарий</th>
                <th style="width: 90px;"></th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $interviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $s = $statusMap[$i->status] ?? ['label'=>$i->status, 'badge'=>'text-bg-light'];
                ?>
                <tr>
                    <td><?php echo e($i->interview_date); ?></td>
                    <td class="fw-semibold"><?php echo e($i->candidate_name); ?></td>
                    <td><?php echo e($i->candidate_phone ?? '—'); ?></td>
                    <td><?php echo e($i->source ?? '—'); ?></td>
                    <td><span class="badge <?php echo e($s['badge']); ?>"><?php echo e($s['label']); ?></span></td>
                    <td><?php echo e($i->comment ?? '—'); ?></td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ⋮
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo e(route('interviews.edit', $i)); ?>">Править</a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST"
                                          action="<?php echo e(route('interviews.destroy', $i)); ?>"
                                          onsubmit="return confirm('Удалить собеседование?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button class="dropdown-item text-danger" type="submit">Удалить</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($interviews->count() === 0): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted p-4">Пока нет собеседований</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?php echo e($interviews->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/interviews/index.blade.php ENDPATH**/ ?>