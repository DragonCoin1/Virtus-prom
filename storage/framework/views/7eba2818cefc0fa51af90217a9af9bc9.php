
<?php $__env->startSection('title', 'Править разноску'); ?>

<?php $__env->startSection('content'); ?>
<h3 class="mb-3">Править разноску</h3>

<?php if($errors->any()): ?>
    <div class="alert alert-danger"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<?php
    $selectedIds = old('leaflet_template_ids');
    if (!is_array($selectedIds)) {
        $selectedIds = $routeAction->templates->pluck('template_id')->toArray();
    }
?>

<form method="POST" action="<?php echo e(route('route_actions.update', $routeAction)); ?>" class="card">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Дата</label>
                <input class="form-control" type="date" name="action_date" required
                       value="<?php echo e(old('action_date', $routeAction->action_date)); ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Промоутер</label>
                <select class="form-select" name="promoter_id" required>
                    <option value="">— выбрать —</option>
                    <?php $__currentLoopData = $promoters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->promoter_id); ?>" <?php if(old('promoter_id', $routeAction->promoter_id)==$p->promoter_id): echo 'selected'; endif; ?>>
                            <?php echo e($p->promoter_full_name); ?><?php echo e($p->promoter_phone ? ' (' . $p->promoter_phone . ')' : ''); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Маршрут</label>
                <select class="form-select" name="route_id" required>
                    <option value="">— выбрать —</option>
                    <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($r->route_id); ?>" <?php if(old('route_id', $routeAction->route_id)==$r->route_id): echo 'selected'; endif; ?>>
                            <?php echo e($r->route_code); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Макеты листовок</label>

                <div class="position-relative" id="templatesSelect">
                    <button type="button" class="btn btn-outline-secondary w-100 text-start" id="templatesBtn">
                        <span id="templatesSelectedText" class="text-truncate d-inline-block" style="max-width: calc(100% - 10px);">
                            — выбрать —
                        </span>
                    </button>

                    <div class="border rounded bg-white shadow-sm p-2 mt-1 d-none"
                         id="templatesMenu"
                         style="position:absolute; z-index: 20; width: 100%; max-height: 260px; overflow:auto;">
                        <?php $__currentLoopData = $leafletTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $isInactive = (int)($t->is_active ?? 1) === 0;
                                $isChecked = in_array($t->template_id, $selectedIds);
                            ?>

                            <div class="form-check d-flex align-items-center justify-content-between">
                                <div>
                                    <input class="form-check-input tmpl-check"
                                           type="checkbox"
                                           name="leaflet_template_ids[]"
                                           value="<?php echo e($t->template_id); ?>"
                                           id="tmpl_<?php echo e($t->template_id); ?>"
                                           data-name="<?php echo e($t->template_name); ?>"
                                           <?php if($isChecked): echo 'checked'; endif; ?>
                                           <?php if($isInactive): echo 'disabled'; endif; ?>>
                                    <label class="form-check-label" for="tmpl_<?php echo e($t->template_id); ?>">
                                        <?php echo e($t->template_name); ?>

                                    </label>
                                </div>

                                <?php if($isInactive): ?>
                                    <span class="text-muted" style="font-size:12px;">выключен</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <?php if($leafletTemplates->count() === 0): ?>
                            <div class="text-muted p-1">Нет активных макетов</div>
                        <?php endif; ?>

                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-primary" id="templatesDone">Готово</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="templatesClear">Очистить</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Листовки (сделано)</label>
                <input class="form-control" type="number" min="0" name="leaflets_total"
                       value="<?php echo e(old('leaflets_total', $routeAction->leaflets_total)); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Листовки (выдано)</label>
                <input class="form-control" type="number" min="0" name="leaflets_issued"
                       value="<?php echo e(old('leaflets_issued', $routeAction->leaflets_issued ?? 0)); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Расклейка (сделано)</label>
                <input class="form-control" type="number" min="0" name="posters_total"
                       value="<?php echo e(old('posters_total', $routeAction->posters_total)); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Расклейка (выдано)</label>
                <input class="form-control" type="number" min="0" name="posters_issued"
                       value="<?php echo e(old('posters_issued', $routeAction->posters_issued ?? 0)); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Визитки (сделано)</label>
                <input class="form-control" type="number" min="0" name="cards_count"
                       value="<?php echo e(old('cards_count', $routeAction->cards_count)); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Визитки (выдано)</label>
                <input class="form-control" type="number" min="0" name="cards_issued"
                       value="<?php echo e(old('cards_issued', $routeAction->cards_issued ?? 0)); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Ящики</label>
                <input class="form-control" type="number" min="0" name="boxes_done"
                       value="<?php echo e(old('boxes_done', $routeAction->boxes_done)); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Оплата</label>
                <input class="form-control" type="number" min="0" name="payment_amount"
                       value="<?php echo e(old('payment_amount', $routeAction->payment_amount ?? 0)); ?>">
            </div>

            <div class="col-md-12">
                <label class="form-label">Комментарий</label>
                <input class="form-control" name="action_comment"
                       value="<?php echo e(old('action_comment', $routeAction->action_comment)); ?>">
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="<?php echo e(route('module.route_actions')); ?>">Назад</a>
    </div>
</form>

<script>
(function () {
    const root = document.getElementById('templatesSelect');
    if (!root) return;

    const btn = document.getElementById('templatesBtn');
    const menu = document.getElementById('templatesMenu');
    const done = document.getElementById('templatesDone');
    const clear = document.getElementById('templatesClear');
    const text = document.getElementById('templatesSelectedText');

    function isOpen(){ return !menu.classList.contains('d-none'); }
    function open(){ menu.classList.remove('d-none'); }
    function close(){ menu.classList.add('d-none'); }

    function updateText(){
        const checks = root.querySelectorAll('.tmpl-check:checked');
        const names = [];
        checks.forEach(c => {
            const n = c.getAttribute('data-name');
            if (n) names.push(n);
        });
        text.textContent = names.length ? names.join(', ') : '— выбрать —';
    }

    btn.addEventListener('click', () => { isOpen() ? close() : open(); });
    done.addEventListener('click', () => { close(); updateText(); });

    clear.addEventListener('click', () => {
        const checks = root.querySelectorAll('.tmpl-check');
        checks.forEach(c => { if (!c.disabled) c.checked = false; });
        updateText();
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) { close(); updateText(); }
    });

    updateText();
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\virtus-prom\resources\views/route_actions/edit.blade.php ENDPATH**/ ?>