<?php $__env->startSection('title', 'Sotuvchi buyurtmalari'); ?>
<?php $__env->startSection('page-title', 'Sotuvchi buyurtmalari'); ?>
<?php $__env->startSection('page-eyebrow', 'Seller fulfillment'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use App\Support\AdminOrderStatusPresenter;
    $tabs = ['all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0]];
    foreach ($statuses as $value => $statusItem) {
        $tabs[(string) $value] = ['label' => $statusItem['label'], 'count' => $counts[$value] ?? 0];
    }

    $statusBadgeClass = function (string $badge): string {
        return match ($badge) {
            'badge-success' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
            'badge-danger' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
            'badge-warning' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
            'badge-info' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
            default => 'text-bg-light border',
        };
    };
?>

<div class="d-flex flex-column gap-4">
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Seller fulfillment','title' => 'Sotuvchi buyurtmalari','subtitle' => 'Seller kesimida yig‘ilgan fulfillment navbati, status o‘zgarishlari va tezkor operatsion boshqaruv shu jadvalda yuradi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Seller fulfillment','title' => 'Sotuvchi buyurtmalari','subtitle' => 'Seller kesimida yig‘ilgan fulfillment navbati, status o‘zgarishlari va tezkor operatsion boshqaruv shu jadvalda yuradi.']); ?>
        <a href="<?php echo e(route('admin.orders.index')); ?>" class="btn-p ghost">
            <i class="bi bi-arrow-left me-2"></i>Asosiy buyurtmalar
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
        <div class="col-12 col-md-6 col-xl-3">
            <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Jami seller orderlar','value' => number_format($counts['all'] ?? 0),'meta' => 'Seller kesimida yaratilgan barcha fulfillment yozuvlari','icon' => 'box-seam','tone' => 'dark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Jami seller orderlar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['all'] ?? 0)),'meta' => 'Seller kesimida yaratilgan barcha fulfillment yozuvlari','icon' => 'box-seam','tone' => 'dark']); ?>
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
        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $statusItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-12 col-md-6 col-xl-3">
                <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => $statusItem['label'],'value' => number_format($counts[$value] ?? 0),'meta' => 'Joriy seller order bosqichidagi yozuvlar','icon' => 'diagram-3','tone' => match($statusItem['badge']) {
                        'badge-success' => 'success',
                        'badge-danger' => 'danger',
                        'badge-warning' => 'warning',
                        'badge-info' => 'info',
                        default => 'primary',
                    }]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusItem['label']),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts[$value] ?? 0)),'meta' => 'Joriy seller order bosqichidagi yozuvlar','icon' => 'diagram-3','tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(match($statusItem['badge']) {
                        'badge-success' => 'success',
                        'badge-danger' => 'danger',
                        'badge-warning' => 'warning',
                        'badge-info' => 'info',
                        default => 'primary',
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Filter va qidiruv','meta' => 'Seller, mijoz yoki order ID bo‘yicha kerakli yozuvni tez topish mumkin.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Filter va qidiruv','meta' => 'Seller, mijoz yoki order ID bo‘yicha kerakli yozuvni tez topish mumkin.']); ?>
        <div class="row g-3 align-items-center">
            <div class="col-12 col-xl-5">
                <form method="GET" class="kc-search">
                    <input type="hidden" name="tab" value="<?php echo e(request('tab', 'all')); ?>">
                    <i class="bi bi-search kc-search__icon"></i>
                    <input
                        type="search"
                        name="search"
                        value="<?php echo e(request('search')); ?>"
                        placeholder="ID, sotuvchi yoki mijoz bo‘yicha qidiring"
                        class="form-control">
                </form>
            </div>
            <div class="col-12 col-xl-7">
                <div class="nav nav-pills gap-2 justify-content-xl-end">
                    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a
                            href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>"
                            class="nav-link <?php echo e((string) $tab === (string) $key ? 'active' : ''); ?>">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Seller orderlar jadvali','meta' => $orders->total() . ' ta yozuv topildi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Seller orderlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($orders->total() . ' ta yozuv topildi.')]); ?>
        <div class="table-responsive kc-table-shell">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Sotuvchi</th>
                        <th>Mijoz</th>
                        <th>Summa</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $customerName = trim(($order->client?->name ?? '') . ' ' . ($order->client?->lastname ?? ''));
                            $address = collect($order->order?->address ?? [])->first();
                            $fallbackName = $address['fullName'] ?? '—';
                            $customerPhone = $order->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
                            $itemsCount = collect($order->order?->items ?? [])->filter(fn ($item) => (int) ($item['seller_id'] ?? 0) === (int) $order->seller_id)->sum(fn ($item) => (int) ($item['count_item'] ?? 1));
                            $orderAmount = (float) ($order->amount ?? 0);
                            $statusCode = $order->status_code ?? \App\Enums\SellerOrderStatusCode::fromLegacy($order->status ?? 1)->value;
                            $statusMeta = $statuses[$statusCode] ?? ['label' => AdminOrderStatusPresenter::sellerOrder($statusCode), 'badge' => 'badge-muted'];
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold">#<?php echo e($order->id); ?></div>
                                <div class="small text-secondary">ORD #<?php echo e($order->order_id); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($order->seller->shop_name ?? '—'); ?></div>
                                <div class="small text-secondary"><?php echo e(trim(($order->seller->firstname ?? '') . ' ' . ($order->seller->lastname ?? '')) ?: 'Sotuvchi'); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($customerName ?: $fallbackName); ?></div>
                                <div class="small text-secondary"><?php echo e($customerPhone); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold font-monospace"><?php echo e(number_format($orderAmount, 0, '.', ' ')); ?> UZS</div>
                                <div class="small text-secondary"><?php echo e($itemsCount); ?> ta mahsulot</div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge rounded-pill <?php echo e($statusBadgeClass($statusMeta['badge'])); ?>"><?php echo e($statusMeta['label']); ?></span>
                                    <form method="POST" action="<?php echo e(route('admin.seller-orders.status', $order)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <select name="status" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $statusItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($value); ?>" <?php if($statusCode === $value): echo 'selected'; endif; ?>><?php echo e($statusItem['label']); ?></option>
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
                                <a href="<?php echo e(route('admin.seller-orders.show', $order)); ?>" class="btn-p primary sm">
                                    <i class="bi bi-eye me-1"></i>Ko‘rish
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">Hech qanday buyurtma topilmadi.</td>
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

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/seller-orders/index.blade.php ENDPATH**/ ?>