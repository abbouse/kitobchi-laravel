<?php $__env->startSection('title', 'Sotuvchilar'); ?>
<?php $__env->startSection('page-title', 'Sotuvchilar'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('success')): ?>
    <div class="alert alert-success kc-flash mb-4">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger kc-flash mb-4">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Seller management','title' => 'Sotuvchilar ro‘yxati','subtitle' => ''.e($sellers->total()).' ta sotuvchi topildi. Moderatsiya, aloqa va faollik holatini shu yerdan boshqaring.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Seller management','title' => 'Sotuvchilar ro‘yxati','subtitle' => ''.e($sellers->total()).' ta sotuvchi topildi. Moderatsiya, aloqa va faollik holatini shu yerdan boshqaring.']); ?>
    <form method="GET" class="kc-search kc-topbar__search">
            <input type="hidden" name="tab" value="<?php echo e(request('tab', 'pending')); ?>">
            <i class="bi bi-search kc-search__icon"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" class="form-control" placeholder="Do‘kon, telefon yoki hudud bo‘yicha qidiring">
    </form>
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

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Kutilayotgan sellerlar','value' => number_format($counts['pending'] ?? 0),'meta' => 'Moderatsiya yoki hujjat tekshiruvini kutayotgan do‘konlar','icon' => 'hourglass-split','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Kutilayotgan sellerlar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['pending'] ?? 0)),'meta' => 'Moderatsiya yoki hujjat tekshiruvini kutayotgan do‘konlar','icon' => 'hourglass-split','tone' => 'warning']); ?>
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
    <div class="col-12 col-md-6 col-xl-3">
        <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Tasdiqlangan','value' => number_format($counts['approved'] ?? 0),'meta' => 'Savdoga chiqqan va faol ishlayotgan sellerlar','icon' => 'shop','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Tasdiqlangan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['approved'] ?? 0)),'meta' => 'Savdoga chiqqan va faol ishlayotgan sellerlar','icon' => 'shop','tone' => 'success']); ?>
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
    <div class="col-12 col-md-6 col-xl-3">
        <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Rad etilgan','value' => number_format($counts['rejected'] ?? 0),'meta' => 'Qayta ko‘rib chiqish yoki tuzatish kutayotgan arizalar','icon' => 'x-octagon','tone' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Rad etilgan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['rejected'] ?? 0)),'meta' => 'Qayta ko‘rib chiqish yoki tuzatish kutayotgan arizalar','icon' => 'x-octagon','tone' => 'danger']); ?>
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
    <div class="col-12 col-md-6 col-xl-3">
        <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Bloklangan','value' => number_format($counts['blocked'] ?? 0),'meta' => 'Policy yoki ogohlantirish sabab cheklangan do‘konlar','icon' => 'shield-lock','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Bloklangan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['blocked'] ?? 0)),'meta' => 'Policy yoki ogohlantirish sabab cheklangan do‘konlar','icon' => 'shield-lock','tone' => 'info']); ?>
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
</div>

<div class="kc-filter-card mb-4">
    <div class="nav nav-pills flex-wrap">
    <?php
        $tabs = [
            'pending'  => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
            'approved' => ['label' => 'Tasdiqlangan', 'count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Rad etilgan', 'count' => $counts['rejected'] ?? 0],
            'blocked'  => ['label' => 'Bloklangan', 'count' => $counts['blocked'] ?? 0],
            'all'      => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
        ];
    ?>
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a
            href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key])); ?>"
            class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>"
        >
            <?php echo e($tabItem['label']); ?>

            <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e($tabItem['count']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
</div>

<?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Sotuvchilar jadvali','meta' => $sellers->total() . ' ta seller yozuvi topildi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Sotuvchilar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sellers->total() . ' ta seller yozuvi topildi.')]); ?>
    <div class="table-responsive kc-table-shell">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Do'kon nomi</th>
                    <th>Tel</th>
                    <th>Viloyat</th>
                    <th>Kitoblar</th>
                    <th>Buyurtmalar</th>
                    <th>Ogohlantirish</th>
                    <th>Holat</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $sellers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seller): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-secondary small">#<?php echo e($seller->id); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                <?php echo $__env->make('a122.partials.avatar', [
                                    'name' => trim(($seller->firstname ?? '') . ' ' . ($seller->lastname ?? '')) ?: ($seller->shop_name ?? 'S'),
                                    'image' => $seller->photo,
                                    'class' => 'w-10 h-10 rounded-2xl text-sm',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate"><?php echo e($seller->shop_name); ?></div>
                                    <?php if($seller->firstname || $seller->lastname): ?>
                                        <div class="small text-secondary text-truncate"><?php echo e(trim($seller->firstname . ' ' . $seller->lastname)); ?></div>
                                    <?php endif; ?>
                                    <?php if($seller->district || $seller->address): ?>
                                        <div class="small text-secondary text-truncate"><?php echo e($seller->district ?: $seller->address); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo e($seller->phone_number ?: '—'); ?></td>
                        <td><?php echo e($seller->region ?: '—'); ?></td>
                        <td>
                            <span class="badge text-bg-light border"><?php echo e($seller->books_count ?? 0); ?></span>
                        </td>
                        <td>
                            <span class="badge text-bg-light border"><?php echo e($seller->orders_count ?? 0); ?></span>
                        </td>
                        <td>
                            <?php $warningCount = (int) ($seller->active_warning_count ?? 0); ?>
                            <span class="badge rounded-pill <?php echo e($warningCount >= 3 ? 'text-bg-danger' : ($warningCount > 0 ? 'text-bg-warning' : 'text-bg-light border')); ?>">
                                <?php echo e($warningCount); ?>/3
                            </span>
                        </td>
                        <td>
                            <?php if($seller->status === 'approved'): ?>
                                <span class="badge rounded-pill text-bg-success">Tasdiqlangan</span>
                            <?php elseif($seller->status === 'pending'): ?>
                                <span class="badge rounded-pill text-bg-warning">Kutilmoqda</span>
                            <?php elseif($seller->status === 'rejected'): ?>
                                <span class="badge rounded-pill text-bg-danger">Rad etilgan</span>
                            <?php elseif($seller->status === 'blocked'): ?>
                                <span class="badge rounded-pill text-bg-dark">Bloklangan</span>
                            <?php else: ?>
                                <span class="badge rounded-pill text-bg-light border"><?php echo e($seller->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                                <?php if($seller->status !== 'approved'): ?>
                                    <form method="POST" action="<?php echo e(route('admin.sellers.approve', $seller)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Tasdiqlash">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo e(route('admin.sellers.reject', $seller)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                                <?php if($seller->status === 'blocked'): ?>
                                    <form method="POST" action="<?php echo e(route('admin.sellers.unblock', $seller)); ?>" onsubmit="return confirm('Sotuvchini blokdan chiqarmoqchimisiz?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Blokdan chiqarish">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="<?php echo e(route('admin.sellers.show', $seller)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko'rish">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-secondary">Hech qanday sotuvchi topilmadi</td>
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

<?php if($sellers->hasPages()): ?>
    <div class="mt-4"><?php echo e($sellers->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/sellers/index.blade.php ENDPATH**/ ?>