<?php $__env->startSection('title', 'Buyurtma #' . $sellerOrder->id); ?>
<?php $__env->startSection('page-title', 'Sotuvchi buyurtmasi'); ?>
<?php $__env->startSection('page-eyebrow', 'Seller fulfillment'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use App\Support\AdminOrderStatusPresenter;
    $customerName = trim(($sellerOrder->client?->name ?? '') . ' ' . ($sellerOrder->client?->lastname ?? '')) ?: ($address['fullName'] ?? '—');
    $customerPhone = $sellerOrder->client?->phone_number ?? ($address['phoneNumber'] ?? '—');
    $statusVal = $sellerOrder->status_code ?? \App\Enums\SellerOrderStatusCode::fromLegacy($sellerOrder->status ?? 1)->value;
    $statusMeta = $statuses[$statusVal] ?? ['label' => AdminOrderStatusPresenter::sellerOrder($statusVal), 'badge' => 'badge-muted'];
    $statusBadgeClass = match ($statusMeta['badge']) {
        'badge-success' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
        'badge-danger' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
        'badge-warning' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
        'badge-info' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
        default => 'text-bg-light border',
    };
?>

<div class="d-flex flex-column gap-4">
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Seller fulfillment','title' => 'Seller buyurtma #' . $sellerOrder->id,'subtitle' => ($sellerOrder->seller->shop_name ?? 'Sotuvchi yo‘q') . ' · ' . $customerName . ' · ' . ($sellerOrder->created_at ? $sellerOrder->created_at->format('d.m.Y H:i') : 'Sana yo‘q')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Seller fulfillment','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Seller buyurtma #' . $sellerOrder->id),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($sellerOrder->seller->shop_name ?? 'Sotuvchi yo‘q') . ' · ' . $customerName . ' · ' . ($sellerOrder->created_at ? $sellerOrder->created_at->format('d.m.Y H:i') : 'Sana yo‘q'))]); ?>
        <a href="<?php echo e(route('admin.seller-orders.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">
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
        <div class="col-12 col-md-6 col-xl-3">
            <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Seller summasi','value' => number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ') . ' UZS','meta' => 'Sellerga tegishli payout qismi','icon' => 'cash-coin','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Seller summasi','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ') . ' UZS'),'meta' => 'Sellerga tegishli payout qismi','icon' => 'cash-coin','tone' => 'success']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Mahsulotlar','value' => number_format($summary['items_count']),'meta' => 'Seller itemlari','icon' => 'box-seam','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Mahsulotlar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($summary['items_count'])),'meta' => 'Seller itemlari','icon' => 'box-seam','tone' => 'info']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Yetkazish turi','value' => $summary['delivery_type'],'meta' => 'Fulfillment yo‘li','icon' => 'truck','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Yetkazish turi','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($summary['delivery_type']),'meta' => 'Fulfillment yo‘li','icon' => 'truck','tone' => 'warning']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Holat','value' => $statusMeta['label'],'meta' => 'Joriy seller bosqichi','icon' => 'diagram-3','tone' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Holat','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusMeta['label']),'meta' => 'Joriy seller bosqichi','icon' => 'diagram-3','tone' => 'primary']); ?>
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

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Buyurtma ma\'lumotlari','meta' => 'Asosiy order, mijoz, summa va yetkazish bo‘yicha tafsilotlar.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Buyurtma ma\'lumotlari','meta' => 'Asosiy order, mijoz, summa va yetkazish bo‘yicha tafsilotlar.']); ?>
                <div class="row g-4 small">
                    <div class="col-sm-6"><div class="text-secondary mb-1">Buyurtma ID</div><div class="fw-semibold">#<?php echo e($sellerOrder->id); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sana</div><div><?php echo e($sellerOrder->created_at ? $sellerOrder->created_at->format('d.m.Y H:i') : '—'); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Asosiy buyurtma</div><div class="fw-semibold"><a href="<?php echo e(route('admin.orders.show', $sellerOrder->order_id)); ?>" class="link-success text-decoration-none">#<?php echo e($sellerOrder->order_id); ?></a></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sotuvchi</div><div class="fw-semibold"><?php if($sellerOrder->seller): ?><a href="<?php echo e(route('admin.sellers.show', $sellerOrder->seller)); ?>" class="link-success text-decoration-none"><?php echo e($sellerOrder->seller->shop_name); ?></a><?php else: ?>—<?php endif; ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Summa</div><div class="fw-bold fs-5"><?php echo e(number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ')); ?> UZS</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Mijoz</div><div><?php if($sellerOrder->client): ?><a href="<?php echo e(route('admin.users.show', $sellerOrder->client)); ?>" class="link-success text-decoration-none"><?php echo e($customerName); ?></a><?php else: ?><?php echo e($customerName); ?><?php endif; ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Telefon</div><div><?php echo e($customerPhone); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Yetkazib berish turi</div><div><?php echo e($summary['delivery_type']); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Mahsulotlar soni</div><div><?php echo e($summary['items_count']); ?> ta</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div><span class="badge rounded-pill <?php echo e($statusBadgeClass); ?>"><?php echo e($statusMeta['label']); ?></span></div></div>
                    <div class="col-12"><div class="text-secondary mb-1">Manzil</div><div><?php echo e($address['fullAddress'] ?? 'Manzil kiritilmagan'); ?></div></div>
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

        <div class="col-12 col-xl-4">
            <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Holatni o\'zgartirish','meta' => 'Seller order statusini shu blokdan yangilash mumkin.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Holatni o\'zgartirish','meta' => 'Seller order statusini shu blokdan yangilash mumkin.']); ?>
                <?php if($errors->any()): ?>
                    <div class="alert alert-danger rounded-4 small">
                        <ul class="mb-0 ps-3">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('admin.seller-orders.status', $sellerOrder)); ?>" class="d-grid gap-3">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <div>
                        <label class="form-label">Yangi holat</label>
                        <select name="status" class="form-select">
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if($statusVal === $value): echo 'selected'; endif; ?>><?php echo e($status['label']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="bi bi-floppy me-2"></i>Saqlash
                    </button>
                </form>
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

    <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Sotuvchiga tegishli mahsulotlar','meta' => 'Aynan shu sellerga biriktirilgan order itemlari va summalari.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Sotuvchiga tegishli mahsulotlar','meta' => 'Aynan shu sellerga biriktirilgan order itemlari va summalari.']); ?>
        <?php if($items->isEmpty()): ?>
            <div class="text-secondary">Bu buyurtma uchun sotuvchiga tegishli mahsulotlar topilmadi.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mahsulot</th>
                            <th>Turi</th>
                            <th>Miqdor</th>
                            <th>Narx</th>
                            <th>Jami</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo e($item['name']); ?></div>
                                    <?php if(!empty($item['author'])): ?>
                                        <div class="small text-secondary"><?php echo e($item['author']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($item['type']); ?></td>
                                <td class="font-monospace"><?php echo e(number_format((int) ($item['count_item'] ?? 1))); ?></td>
                                <td class="font-monospace"><?php echo e(number_format((float) ($item['price'] ?? 0), 0, '.', ' ')); ?> UZS</td>
                                <td class="fw-semibold font-monospace"><?php echo e(number_format((float) (($item['price'] ?? 0) * ($item['count_item'] ?? 1)), 0, '.', ' ')); ?> UZS</td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/seller-orders/show.blade.php ENDPATH**/ ?>