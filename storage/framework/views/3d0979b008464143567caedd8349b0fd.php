<?php $__env->startSection('title', 'Vakansiyalar'); ?>
<?php $__env->startSection('page-title', 'Vakansiyalar'); ?>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('heading', null, []); ?> Vakansiyalar <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Ommaviy sahifa: <a href="<?php echo e(route('careers.index')); ?>" target="_blank" rel="noopener" class="text-[var(--p-accent)]">/careers</a> <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $attributes = $__attributesOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__attributesOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $component = $__componentOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__componentOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>

<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Vakansiyalar ro‘yxati</div>
        <div class="a122-index-header__meta"><?php echo e($vacancies->total()); ?> ta e’lon topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Lavozim, joylashuv yoki tur bo‘yicha qidiring">
        </form>
        <a href="<?php echo e(route('admin.jobs.create')); ?>" class="btn-p primary">
            <i class="bi bi-plus-lg"></i> Yangi vakansiya
        </a>
    </div>
</div>

<div class="tab-pills fade-up mb-3">
    <?php $__currentLoopData = [
        'active' => ['Faol', $counts['active'] ?? 0],
        'inactive' => ['Yashirin', $counts['inactive'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
            <?php echo e($label); ?> <span><?php echo e($count); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php if(session('success')): ?>
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

<div class="p-card p-0">
    <div class="table-responsive kc-twrap">
        <table class="p-table" data-index-grid>
            <thead>
                <tr>
                    <th>#</th>
                    <th class="w-10"></th>
                    <th>Nomi</th>
                    <th>Turi</th>
                    <th>Joy</th>
                    <th>Tartib</th>
                    <th>Holat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $vacancies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $ic = $row->resolvedIcon();
                        $bi = match ($ic) {
                            'code' => 'code-slash',
                            'palette' => 'palette-fill',
                            'shop' => 'shop',
                            'megaphone' => 'megaphone-fill',
                            'people' => 'people-fill',
                            'chart' => 'graph-up-arrow',
                            default => 'briefcase-fill',
                        };
                    ?>
                    <tr>
                        <td class="p-td-id"><?php echo e($row->id); ?></td>
                        <td class="text-center text-slate-400" title="<?php echo e(\App\Models\Vacancy::iconOptions()[$ic] ?? ''); ?>">
                            <i class="bi bi-<?php echo e($bi); ?>"></i>
                        </td>
                        <td class="p-td-strong"><?php echo e($row->title); ?></td>
                        <td class="p-td-muted"><?php echo e($row->contract_type ?: '—'); ?></td>
                        <td class="p-td-muted"><?php echo e($row->location ?: '—'); ?></td>
                        <td><?php echo e($row->sort_order); ?></td>
                        <td>
                            <span class="s-pill <?php echo e($row->is_active ? 'success' : 'muted'); ?>">
                                <?php echo e($row->is_active ? 'Faol' : 'Yashirin'); ?>

                            </span>
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a href="<?php echo e(route('admin.jobs.edit', $row)); ?>" class="btn-p ghost sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('admin.jobs.toggle', $row)); ?>" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn-p ghost sm" title="Ko‘rinishni almashtirish">
                                        <i class="bi bi-<?php echo e($row->is_active ? 'eye-slash' : 'eye'); ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.jobs.destroy', $row)); ?>"
                                      class="inline" onsubmit="return confirm('O‘chirilsinmi?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn-p ghost sm text-red-400">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-[var(--p-hint)]">
                            Hozircha vakansiya yo‘q. «Yangi vakansiya» tugmasidan qo‘shing.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($vacancies->hasPages()): ?>
    <div class="mt-4"><?php echo e($vacancies->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/jobs/index.blade.php ENDPATH**/ ?>