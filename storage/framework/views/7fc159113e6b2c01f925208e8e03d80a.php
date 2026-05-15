<?php $__env->startSection('title', 'Tranzaksiyalar'); ?>
<?php $__env->startSection('page-title', 'Sotuvchi tranzaksiyalari'); ?>
<?php $__env->startSection('page-eyebrow', 'Payout operations'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $tabs = [
        'pending'  => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
        'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
        'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
        'all'      => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    ];

    $statusBadgeClass = function (?string $status): string {
        return match ($status) {
            'approved' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
            'pending' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
            'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
            default => 'text-bg-light border',
        };
    };
?>

<div class="d-flex flex-column gap-4">
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Payout operations','title' => 'Tranzaksiyalar ro‘yxati','subtitle' => 'Seller payout yozuvlari, tasdiqlash navbati va moliyaviy oqim holatlari shu bo‘limda boshqariladi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Payout operations','title' => 'Tranzaksiyalar ro‘yxati','subtitle' => 'Seller payout yozuvlari, tasdiqlash navbati va moliyaviy oqim holatlari shu bo‘limda boshqariladi.']); ?>
        <a href="<?php echo e(route('admin.sellers.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-shop me-2"></i>Sotuvchilar
        </a>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

    <?php if(session('success')): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-12 col-md-6 col-xl-3">
                <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => $tabItem['label'],'value' => number_format($tabItem['count']),'meta' => 'Payout navbatidagi yozuvlar','icon' => 'cash-stack','tone' => match($key) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'dark',
                    }]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tabItem['label']),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($tabItem['count'])),'meta' => 'Payout navbatidagi yozuvlar','icon' => 'cash-stack','tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(match($key) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'dark',
                    })]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Filter va qidiruv','meta' => 'ID, seller yoki summa bo‘yicha kerakli payout yozuvini toping.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Filter va qidiruv','meta' => 'ID, seller yoki summa bo‘yicha kerakli payout yozuvini toping.']); ?>
        <div class="row g-3 align-items-center">
            <div class="col-12 col-xl-5">
                <form method="GET" class="position-relative">
                    <input type="hidden" name="tab" value="<?php echo e(request('tab', 'pending')); ?>">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                    <input
                        type="search"
                        name="search"
                        value="<?php echo e(request('search')); ?>"
                        placeholder="ID, sotuvchi yoki summa bo‘yicha qidiring"
                        class="form-control rounded-pill ps-5">
                </form>
            </div>
            <div class="col-12 col-xl-7">
                <div class="nav nav-pills gap-2 justify-content-xl-end">
                    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a
                            href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>"
                            class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
                            <?php echo e($tabItem['label']); ?>

                            <span class="badge rounded-pill text-bg-light ms-2 font-monospace"><?php echo e(number_format($tabItem['count'])); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Tranzaksiyalar jadvali','meta' => $transactions->total() . ' ta tranzaksiya topildi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tranzaksiyalar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactions->total() . ' ta tranzaksiya topildi.')]); ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Sotuvchi</th>
                        <th>Miqdor</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo e($transaction->id); ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo e($transaction->seller->shop_name ?? '—'); ?></div>
                            </td>
                            <td class="fw-semibold font-monospace"><?php echo e(number_format((float)($transaction->amount ?? 0), 0, '.', ' ')); ?> UZS</td>
                            <td><span class="badge rounded-pill <?php echo e($statusBadgeClass($transaction->status)); ?>"><?php echo e($transaction->status_label ?? $transaction->status ?? '—'); ?></span></td>
                            <td>
                                <div class="fw-semibold"><?php echo e($transaction->created_at ? $transaction->created_at->format('d.m.Y') : '—'); ?></div>
                                <div class="small text-secondary"><?php echo e($transaction->created_at ? $transaction->created_at->format('H:i') : '—'); ?></div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                    <?php if($transaction->status === 'pending'): ?>
                                        <form method="POST" action="<?php echo e(route('admin.transactions.approve', $transaction)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                <i class="bi bi-check2-circle me-1"></i>Tasdiqlash
                                            </button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('admin.transactions.reject', $transaction)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                <i class="bi bi-x-circle me-1"></i>Rad etish
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?php echo e(route('admin.transactions.show', $transaction)); ?>" class="btn btn-sm btn-dark rounded-pill px-3">
                                        <i class="bi bi-eye me-1"></i>Ko‘rish
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">Hech qanday tranzaksiya topilmadi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

    <?php if($transactions->hasPages()): ?>
        <div><?php echo e($transactions->links('a122.partials.pagination')); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/transactions/index.blade.php ENDPATH**/ ?>