<?php $__env->startSection('title', 'Kuryer buyurtmasi #' . $courierOrder->id); ?>
<?php $__env->startSection('page-title', 'Kuryer buyurtmasi'); ?>
<?php $__env->startSection('page-eyebrow', 'Last-mile operations'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use App\Support\AdminOrderStatusPresenter;
    $statusKey = (string) ($courierOrder->status ?? '');
    $statusLabel = $statuses[$statusKey]['label'] ?? AdminOrderStatusPresenter::courierOrder($statusKey);
    $statusBadgeClass = match ($statusKey) {
        'delivered', 'customer_received' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
        'pending', 'pay_process' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
        'in_delivery' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
        'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
        default => 'text-bg-light border',
    };
?>

<div class="d-flex flex-column gap-4">
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Last-mile operations','title' => 'Kuryer buyurtmasi #' . $courierOrder->id,'subtitle' => (trim(($courierOrder->courier->first_name ?? '') . ' ' . ($courierOrder->courier->last_name ?? '')) ?: 'Kuryer yo‘q') . ' · ' . ($courierOrder->created_at ? $courierOrder->created_at->format('d.m.Y H:i') : 'Sana yo‘q')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Last-mile operations','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Kuryer buyurtmasi #' . $courierOrder->id),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((trim(($courierOrder->courier->first_name ?? '') . ' ' . ($courierOrder->courier->last_name ?? '')) ?: 'Kuryer yo‘q') . ' · ' . ($courierOrder->created_at ? $courierOrder->created_at->format('d.m.Y H:i') : 'Sana yo‘q'))]); ?>
        <a href="<?php echo e(route('admin.courier-orders.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Ro‘yxatga qaytish
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
        <div class="col-12 col-md-6 col-xl-3"><?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Joriy holat','value' => $statusLabel,'meta' => 'Courier bosqichi','icon' => 'truck','tone' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Joriy holat','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusLabel),'meta' => 'Courier bosqichi','icon' => 'truck','tone' => 'primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?></div>
        <div class="col-12 col-md-6 col-xl-3"><?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Summa','value' => number_format((float)($courierOrder->amount ?? $courierOrder->total ?? 0), 0, '.', ' ') . ' UZS','meta' => 'Yetkazish yozuvi summasi','icon' => 'cash-coin','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Summa','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format((float)($courierOrder->amount ?? $courierOrder->total ?? 0), 0, '.', ' ') . ' UZS'),'meta' => 'Yetkazish yozuvi summasi','icon' => 'cash-coin','tone' => 'success']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?></div>
        <div class="col-12 col-md-6 col-xl-3"><?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Kuryer','value' => $courierOrder->courier ? 'Biriktirilgan' : 'Yo‘q','meta' => $courierOrder->courier?->region ?: 'Hudud yo‘q','icon' => 'person-badge','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Kuryer','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($courierOrder->courier ? 'Biriktirilgan' : 'Yo‘q'),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($courierOrder->courier?->region ?: 'Hudud yo‘q'),'icon' => 'person-badge','tone' => 'info']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?></div>
        <div class="col-12 col-md-6 col-xl-3"><?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Sana','value' => optional($courierOrder->created_at)->format('d.m') ?: '—','meta' => optional($courierOrder->created_at)->format('H:i') ?: 'Vaqt yo‘q','icon' => 'calendar-event','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Sana','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($courierOrder->created_at)->format('d.m') ?: '—'),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($courierOrder->created_at)->format('H:i') ?: 'Vaqt yo‘q'),'icon' => 'calendar-event','tone' => 'warning']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Kuryer ma\'lumotlari','meta' => 'Biriktirilgan kuryerning profil va aloqa ma’lumotlari.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kuryer ma\'lumotlari','meta' => 'Biriktirilgan kuryerning profil va aloqa ma’lumotlari.']); ?>
                <?php if($courierOrder->courier): ?>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php if($courierOrder->courier->photo): ?>
                            <img
                                src="<?php echo e(Str::startsWith($courierOrder->courier->photo, 'http') ? $courierOrder->courier->photo : asset('storage/' . $courierOrder->courier->photo)); ?>"
                                alt="Kuryer"
                                class="rounded-circle object-fit-cover"
                                width="56"
                                height="56">
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-secondary" style="width:56px;height:56px;">
                                <?php echo e(strtoupper(substr($courierOrder->courier->first_name ?? 'K', 0, 1))); ?>

                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="fw-bold">
                                <a href="<?php echo e(route('admin.couriers.show', $courierOrder->courier)); ?>" class="link-success text-decoration-none">
                                    <?php echo e(trim(($courierOrder->courier->first_name ?? '') . ' ' . ($courierOrder->courier->last_name ?? '')) ?: '—'); ?>

                                </a>
                            </div>
                            <div class="small text-secondary"><?php echo e($courierOrder->courier->phone_number ?? $courierOrder->courier->phone ?? '—'); ?></div>
                        </div>
                    </div>

                    <div class="row g-4 small">
                        <div class="col-sm-6"><div class="text-secondary mb-1">Viloyat</div><div><?php echo e($courierOrder->courier->region ?? '—'); ?></div></div>
                        <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div><?php echo e(\App\Support\AdminOrderStatusPresenter::courierProfile($courierOrder->courier->status ?? null)); ?></div></div>
                    </div>
                <?php else: ?>
                    <div class="text-secondary">Kuryer ma'lumotlari mavjud emas.</div>
                <?php endif; ?>
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
        </div>

        <div class="col-12 col-lg-6">
            <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Buyurtma ma\'lumotlari','meta' => 'Kuryerga tushgan orderning foydalanuvchi, manzil va summa tafsilotlari.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Buyurtma ma\'lumotlari','meta' => 'Kuryerga tushgan orderning foydalanuvchi, manzil va summa tafsilotlari.']); ?>
                <div class="row g-4 small">
                    <div class="col-sm-6"><div class="text-secondary mb-1">Buyurtma ID</div><div class="fw-semibold">#<?php echo e($courierOrder->id); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div><span class="badge rounded-pill <?php echo e($statusBadgeClass); ?>"><?php echo e($statusLabel); ?></span></div></div>
                    <div class="col-sm-6">
                        <div class="text-secondary mb-1">Foydalanuvchi</div>
                        <div>
                            <?php if($courierOrder->user): ?>
                                <a href="<?php echo e(route('admin.users.show', $courierOrder->user)); ?>" class="link-success text-decoration-none fw-semibold">
                                    <?php echo e(trim(($courierOrder->user->first_name ?? $courierOrder->user->name ?? '') . ' ' . ($courierOrder->user->last_name ?? '')) ?: '—'); ?>

                                </a>
                                <div class="small text-secondary"><?php echo e($courierOrder->user->phone_number ?? $courierOrder->user->phone ?? 'Telefon yo‘q'); ?></div>
                            <?php else: ?>
                                <span class="text-secondary">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sana</div><div><?php echo e($courierOrder->created_at ? $courierOrder->created_at->format('d.m.Y H:i') : '—'); ?></div></div>
                    <?php if($courierOrder->address ?? $courierOrder->delivery_address): ?>
                        <div class="col-12"><div class="text-secondary mb-1">Manzil</div><div><?php echo e($courierOrder->address ?? $courierOrder->delivery_address); ?></div></div>
                    <?php endif; ?>
                    <?php if($courierOrder->amount ?? $courierOrder->total): ?>
                        <div class="col-sm-6"><div class="text-secondary mb-1">Summa</div><div class="fw-bold fs-5"><?php echo e(number_format((float)($courierOrder->amount ?? $courierOrder->total ?? 0), 0, '.', ' ')); ?> UZS</div></div>
                    <?php endif; ?>
                    <?php if($courierOrder->note ?? $courierOrder->comment): ?>
                        <div class="col-12"><div class="text-secondary mb-1">Izoh</div><div><?php echo e($courierOrder->note ?? $courierOrder->comment); ?></div></div>
                    <?php endif; ?>
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
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/courier-orders/show.blade.php ENDPATH**/ ?>