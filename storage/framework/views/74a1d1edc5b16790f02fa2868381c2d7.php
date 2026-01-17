
<?php $__env->startSection('title', 'Корректировка зарплаты'); ?>

<?php $__env->startSection('content'); ?>
<h3 class="mb-3">Добавить корректировку</h3>

<?php if($errors->any()): ?>
    <div class="alert alert-danger"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('salary.adjustments.store')); ?>" class="card">
    <?php echo csrf_field(); ?>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Промоутер</label>
                <select class="form-select" name="promoter_id" required>
                    <option value="">— выбрать —</option>
                    <?php $__currentLoopData = $promoters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->promoter_id); ?>" <?php if(old('promoter_id')==$p->promoter_id): echo 'selected'; endif; ?>>
                            <?php echo e($p->promoter_full_name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Дата</label>
                <input class="form-control" type="date" name="adj_date"
                       value="<?php echo e(old('adj_date', date('Y-m-d'))); ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Сумма (+ / -)</label>
                <input class="form-control" type="number" name="amount" value="<?php echo e(old('amount', 0)); ?>" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Комментарий</label>
                <input class="form-control" name="comment" value="<?php echo e(old('comment')); ?>">
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="<?php echo e(route('salary.index')); ?>">Назад</a>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/salary/adjustment_create.blade.php ENDPATH**/ ?>