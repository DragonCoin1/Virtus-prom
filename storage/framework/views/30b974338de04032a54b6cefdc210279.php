

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <h3 class="mb-3">Dashboard</h3>

    <div class="card">
        <div class="card-body">
            Вы вошли как: <b><?php echo e(auth()->user()->user_full_name); ?></b> (<?php echo e(auth()->user()->user_login); ?>)
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/home.blade.php ENDPATH**/ ?>