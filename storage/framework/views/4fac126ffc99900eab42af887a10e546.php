<?php $__env->startSection('title', 'Karyera arizalari'); ?>
<?php $__env->startSection('page-title', 'Karyera arizalari'); ?>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('heading', null, []); ?> Karyera arizalari <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Sayt /careers — vakansiya va ochiq murojaatlar <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>

<?php if(session('success')): ?>
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="tab-pills fade-up mb-3">
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1])); ?>"
       class="tab-pill <?php echo e($tab === 'all' ? 'active' : ''); ?>">
        Barchasi <span class="tab-badge"><?php echo e($counts['all']); ?></span>
    </a>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => 'vacancy', 'page' => 1])); ?>"
       class="tab-pill <?php echo e($tab === 'vacancy' ? 'active' : ''); ?>">
        Vakansiya <span class="tab-badge"><?php echo e($counts['vacancy']); ?></span>
    </a>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => 'inquiry', 'page' => 1])); ?>"
       class="tab-pill <?php echo e($tab === 'inquiry' ? 'active' : ''); ?>">
        Ochiq murojaat <span class="tab-badge"><?php echo e($counts['inquiry']); ?></span>
    </a>
</div>

<div class="filter-bar mb-3">
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
        <input type="search" name="search" class="p-form-control p-filter-input-wide" placeholder="ID, ism, email, Telegram…"
               value="<?php echo e(request('search')); ?>">
        <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
        <a href="<?php echo e(route('panel.career-applications.index', ['tab' => $tab])); ?>" class="btn-p ghost"><i class="bi bi-x"></i></a>
    </form>
</div>

<div class="p-card p-0">
    <div class="table-responsive kc-twrap">
        <table class="p-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Turi</th>
                    <th>Lavozim / —</th>
                    <th>Ism</th>
                    <th>Email</th>
                    <th>Telegram</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $st = $statuses[$row->status] ?? ['label' => $row->status, 'class' => 'ob-p'];
                    ?>
                    <tr>
                        <td class="p-td-id">#<?php echo e($row->id); ?></td>
                        <td>
                            <span class="s-pill <?php echo e($row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'muted' : 'accent'); ?>">
                                <?php echo e($row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Murojaat' : 'Vakansiya'); ?>

                            </span>
                        </td>
                        <td class="p-td-max p-td-title"><?php echo e($row->vacancy?->title ?? '—'); ?></td>
                        <td class="p-td-strong"><?php echo e($row->full_name); ?></td>
                        <td class="p-td-muted"><?php echo e($row->email); ?></td>
                        <td class="p-td-hint"><?php echo e($row->telegram_username ? '@'.$row->telegram_username : '—'); ?></td>
                        <td><span class="o-badge <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span></td>
                        <td class="p-td-hint"><?php echo e($row->created_at?->format('d.m.Y H:i')); ?></td>
                        <td>
                            <a href="<?php echo e(route('panel.career-applications.show', $row)); ?>" class="btn-p ghost sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="py-8 text-center text-[var(--p-hint)]">Hozircha ariza yo‘q.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <?php echo e($applications->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/career-applications/index.blade.php ENDPATH**/ ?>