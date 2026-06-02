<?php $__env->startSection('title', 'Kuryer buyurtmalari'); ?>
<?php $__env->startSection('page-title', 'Kuryer buyurtmalari'); ?>
<?php $__env->startSection('page-eyebrow', 'Last-mile operations'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use App\Support\AdminOrderStatusPresenter;
    $tabs = [
        'pay_process' => ["To'lov jarayonida", $counts['pay_process'] ?? 0],
        'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
        'in_delivery' => ["Yo'lda", $counts['in_delivery'] ?? 0],
        'delivered' => ['Yetib bordi', $counts['delivered'] ?? 0],
        'customer_received' => ['Mijoz qabul qildi', $counts['customer_received'] ?? 0],
        'rejected' => ['Bekor qilingan', $counts['rejected'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
    ];

    $statusBadgeClass = function (string $key): string {
        return match ($key) {
            'delivered', 'customer_received' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
            'pending', 'pay_process' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
            'in_delivery' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
            'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
            default => 'text-bg-light border',
        };
    };
?>

<div class="d-flex flex-column gap-4">
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Last-mile operations','title' => 'Kuryer buyurtmalari','subtitle' => 'Kuryerga biriktirilgan oxirgi mil yozuvlari, foydalanuvchi kontaktlari va tezkor status almashuvlari shu navbatda boshqariladi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Last-mile operations','title' => 'Kuryer buyurtmalari','subtitle' => 'Kuryerga biriktirilgan oxirgi mil yozuvlari, foydalanuvchi kontaktlari va tezkor status almashuvlari shu navbatda boshqariladi.']); ?>
        <a href="<?php echo e(route('admin.couriers.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-bicycle me-2"></i>Kuryerlar
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
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-12 col-md-6 col-xl-2">
                <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => $label,'value' => number_format($count),'meta' => 'Kuryer navbatidagi yozuvlar','icon' => 'truck','tone' => match($key) {
                        'delivered', 'customer_received' => 'success',
                        'pending', 'pay_process' => 'warning',
                        'in_delivery' => 'info',
                        'rejected' => 'danger',
                        default => 'dark',
                    }]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($count)),'meta' => 'Kuryer navbatidagi yozuvlar','icon' => 'truck','tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(match($key) {
                        'delivered', 'customer_received' => 'success',
                        'pending', 'pay_process' => 'warning',
                        'in_delivery' => 'info',
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Filter va qidiruv','meta' => 'Kuryer, foydalanuvchi yoki delivery yozuvi bo‘yicha kerakli entryni tez toping.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Filter va qidiruv','meta' => 'Kuryer, foydalanuvchi yoki delivery yozuvi bo‘yicha kerakli entryni tez toping.']); ?>
        <div class="row g-3 align-items-center">
            <div class="col-12 col-xl-5">
                <form method="GET" class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                    <input
                        type="search"
                        name="search"
                        value="<?php echo e(request('search')); ?>"
                        placeholder="ID, kuryer yoki foydalanuvchi bo‘yicha qidiring"
                        class="form-control rounded-pill ps-5">
                </form>
            </div>
            <div class="col-12 col-xl-7">
                <div class="nav nav-pills gap-2 justify-content-xl-end">
                    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a
                            href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>"
                            class="nav-link <?php echo e(($tab ?? 'all') === $key ? 'active' : ''); ?>">
                            <?php echo e($label); ?>

                            <span class="badge rounded-pill text-bg-light ms-2 font-monospace"><?php echo e(number_format($count)); ?></span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Kuryer orderlar jadvali','meta' => $orders->total() . ' ta delivery yozuvi topildi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kuryer orderlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($orders->total() . ' ta delivery yozuvi topildi.')]); ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Kuryer</th>
                        <th>Foydalanuvchi</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo e($order->id); ?></td>
                            <td>
                                <?php if($order->courier): ?>
                                    <div class="fw-semibold"><?php echo e(trim(($order->courier->first_name ?? '') . ' ' . ($order->courier->last_name ?? '')) ?: '—'); ?></div>
                                    <div class="small text-secondary"><?php echo e($order->courier->phone_number ?? $order->courier->phone ?? 'Telefon yo‘q'); ?></div>
                                <?php else: ?>
                                    <span class="text-secondary">Kuryer yo‘q</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($order->user): ?>
                                    <div class="fw-semibold"><?php echo e(trim(($order->user->first_name ?? $order->user->name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: '—'); ?></div>
                                    <div class="small text-secondary"><?php echo e($order->user->phone_number ?? $order->user->phone ?? 'Telefon yo‘q'); ?></div>
                                <?php else: ?>
                                    <span class="text-secondary">Foydalanuvchi yo‘q</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge rounded-pill <?php echo e($statusBadgeClass((string) ($order->status ?? ''))); ?>">
                                        <?php echo e($statuses[$order->status]['label'] ?? AdminOrderStatusPresenter::courierOrder($order->status ?? null)); ?>

                                    </span>
                                    <form method="POST" action="<?php echo e(route('admin.courier-orders.status', $order)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <select name="status" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $statusItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($value); ?>" <?php if(($order->status ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($statusItem['label']); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($order->created_at ? $order->created_at->format('d.m.Y') : '—'); ?></div>
                                <div class="small text-secondary"><?php echo e($order->created_at ? $order->created_at->format('H:i') : '—'); ?></div>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo e(route('admin.courier-orders.show', $order)); ?>" class="btn btn-sm btn-dark rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i>Ko‘rish
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">Hech qanday kuryer buyurtmasi topilmadi.</td>
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

    <?php if($orders->hasPages()): ?>
        <div><?php echo e($orders->links('a122.partials.pagination')); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/courier-orders/index.blade.php ENDPATH**/ ?>