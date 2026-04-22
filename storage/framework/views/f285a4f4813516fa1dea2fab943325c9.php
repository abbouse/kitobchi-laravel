<?php $__env->startSection('title', 'Karyera arizalari'); ?>
<?php $__env->startSection('page-title', 'Karyera arizalari'); ?>

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
     <?php $__env->slot('heading', null, []); ?> Karyera arizalari <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Vakansiya bo'yicha kelgan nomzodlar va umumiy murojaatlar shu yerda bir xil standartda ko'rinadi. <?php $__env->endSlot(); ?>
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

<?php if(session('success')): ?>
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
    <?php $__currentLoopData = [
        [$counts['all'], 'Jami ariza', 'accent', 'bi-briefcase'],
        [$counts['vacancy'], 'Vakansiya', 'info', 'bi-person-workspace'],
        [$counts['inquiry'], 'Ochiq murojaat', 'warning', 'bi-chat-square-text'],
        [$counts['new'], 'Yangi', 'success', 'bi-stars'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $tone, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="p-card flex items-center gap-3" style="padding:14px">
            <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-<?php echo e($tone); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($tone); ?>)">
                <i class="bi <?php echo e($icon); ?>"></i>
            </div>
            <div>
                <div style="font-size:22px;font-weight:700;color:var(--p-text)"><?php echo e($value); ?></div>
                <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)"><?php echo e($label); ?></div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

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

<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Arizalar ro'yxati</div>
        <div class="a122-index-header__meta"><?php echo e($applications->total()); ?> ta ariza topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID, ism, email yoki Telegram bo'yicha qidiring">
        </form>
        <?php if(request('search')): ?>
            <a href="<?php echo e(route('admin.job-applications.index', ['tab' => $tab])); ?>" class="btn-p ghost">Tozalash</a>
        <?php endif; ?>
    </div>
</div>

<div class="p-card p-0">
    <div class="table-responsive kc-twrap">
        <table class="p-table" data-index-grid>
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
                        <td>
                            <form method="POST" action="<?php echo e(route('admin.job-applications.status', $row)); ?>" class="inline-flex">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <select name="status" class="status-select status-select--compact" onchange="this.form.submit()">
                                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($key); ?>" <?php echo e($row->status === $key ? 'selected' : ''); ?>><?php echo e($status['label']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </form>
                        </td>
                        <td class="p-td-hint"><?php echo e($row->created_at?->format('d.m.Y H:i')); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.job-applications.show', $row)); ?>" class="btn-p ghost sm">
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
    <?php echo e($applications->links('a122.partials.pagination')); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/job-applications/index.blade.php ENDPATH**/ ?>