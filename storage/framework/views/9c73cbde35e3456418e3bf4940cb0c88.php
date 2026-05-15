<?php $__env->startSection('title', 'Tranzaksiya #' . $transaction->id); ?>
<?php $__env->startSection('page-title', 'Tranzaksiya tafsilotlari'); ?>
<?php $__env->startSection('page-eyebrow', 'Payout operations'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $statusTone = $transaction->status === 'approved'
        ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
        : ($transaction->status === 'rejected'
            ? 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis'
            : 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis');
    $margin = (float) ($transaction->commissionPrice ?? 0);
    $gross = (float) ($transaction->amount ?? 0);
    $net = (float) ($transaction->netAmount ?? 0);
?>

<div class="d-flex flex-column gap-4">
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Payout operations','title' => '#TRX-' . $transaction->id,'subtitle' => ($transaction->seller?->shop_name ?: 'Seller yo‘q') . ' · ' . optional($transaction->created_at)->format('d.m.Y H:i')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Payout operations','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('#TRX-' . $transaction->id),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($transaction->seller?->shop_name ?: 'Seller yo‘q') . ' · ' . optional($transaction->created_at)->format('d.m.Y H:i'))]); ?>
        <a href="<?php echo e(route('admin.transactions.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Brutto','value' => number_format($gross, 0, '.', ' ') . ' UZS','meta' => 'Umumiy payout summasi','icon' => 'wallet2','tone' => 'dark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Brutto','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($gross, 0, '.', ' ') . ' UZS'),'meta' => 'Umumiy payout summasi','icon' => 'wallet2','tone' => 'dark']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Komissiya','value' => number_format($margin, 0, '.', ' ') . ' UZS','meta' => ($transaction->commissionPercent ?: 0) . '%','icon' => 'percent','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Komissiya','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($margin, 0, '.', ' ') . ' UZS'),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($transaction->commissionPercent ?: 0) . '%'),'icon' => 'percent','tone' => 'warning']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Sof summa','value' => number_format($net, 0, '.', ' ') . ' UZS','meta' => 'Sellerga tushadigan qism','icon' => 'cash-coin','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Sof summa','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($net, 0, '.', ' ') . ' UZS'),'meta' => 'Sellerga tushadigan qism','icon' => 'cash-coin','tone' => 'success']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Holat','value' => $transaction->status_label,'meta' => 'Joriy payout bosqichi','icon' => 'diagram-3','tone' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Holat','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transaction->status_label),'meta' => 'Joriy payout bosqichi','icon' => 'diagram-3','tone' => 'primary']); ?>
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
        <div class="col-12 col-xl-8">
            <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Tranzaksiya ma’lumotlari','meta' => 'Payout yozuvi, seller konteksti va hisob-kitob tarkibi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tranzaksiya ma’lumotlari','meta' => 'Payout yozuvi, seller konteksti va hisob-kitob tarkibi.']); ?>
                <div class="row g-4 small">
                    <div class="col-sm-6"><div class="text-secondary mb-1">Tranzaksiya ID</div><div class="fw-semibold">#<?php echo e($transaction->id); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sana</div><div><?php echo e($transaction->created_at ? $transaction->created_at->format('d.m.Y H:i') : '—'); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sotuvchi</div><div><?php if($transaction->seller): ?><a href="<?php echo e(route('admin.sellers.show', $transaction->seller)); ?>" class="link-success text-decoration-none fw-semibold"><?php echo e($transaction->seller->shop_name); ?></a><?php else: ?>—<?php endif; ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Telefon</div><div><?php echo e($transaction->seller?->phone_number ?: '—'); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Brutto miqdor</div><div><?php echo e(number_format((float)($transaction->amount ?? 0), 0, '.', ' ')); ?> UZS</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div><span class="badge rounded-pill <?php echo e($statusTone); ?>"><?php echo e($transaction->status_label); ?></span></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Komissiya %</div><div><?php echo e($transaction->commissionPercent ?: '—'); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Komissiya summasi</div><div><?php echo e($transaction->commissionPrice ? number_format((float)$transaction->commissionPrice, 0, '.', ' ') . ' UZS' : '—'); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sof summa</div><div><?php echo e($transaction->netAmount ? number_format((float)$transaction->netAmount, 0, '.', ' ') . ' UZS' : '—'); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Karta</div><div><?php echo e($transaction->card ?: '—'); ?></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Yangilangan</div><div><?php echo e(optional($transaction->updated_at)->format('d.m.Y H:i') ?: '—'); ?></div></div>
                </div>

                <?php if($transaction->comment ?? $transaction->note ?? $transaction->rejected_desc): ?>
                    <div class="mt-4 pt-4 border-top">
                        <div class="fw-semibold mb-2">Izoh</div>
                        <div class="text-secondary"><?php echo e($transaction->comment ?? $transaction->note ?? $transaction->rejected_desc); ?></div>
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

        <div class="col-12 col-xl-4">
            <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Seller summary','meta' => 'Shu seller bo‘yicha payout oqimi va operatsion actionlar.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Seller summary','meta' => 'Shu seller bo‘yicha payout oqimi va operatsion actionlar.']); ?>
                <div class="d-grid gap-3">
                    <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Tasdiqlangan tranzaksiyalar','value' => number_format((int) $sellerTotals['approved_count']),'icon' => 'check2-circle','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Tasdiqlangan tranzaksiyalar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format((int) $sellerTotals['approved_count'])),'icon' => 'check2-circle','tone' => 'success']); ?>
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
                    <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Tasdiqlangan summa','value' => number_format((float) $sellerTotals['approved_sum'], 0, '.', ' ') . ' UZS','icon' => 'bank','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Tasdiqlangan summa','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format((float) $sellerTotals['approved_sum'], 0, '.', ' ') . ' UZS'),'icon' => 'bank','tone' => 'info']); ?>
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
                    <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Pending summa','value' => number_format((float) $sellerTotals['pending_sum'], 0, '.', ' ') . ' UZS','icon' => 'hourglass-split','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Pending summa','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format((float) $sellerTotals['pending_sum'], 0, '.', ' ') . ' UZS'),'icon' => 'hourglass-split','tone' => 'warning']); ?>
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

                <?php if($transaction->status === 'pending'): ?>
                    <div class="d-grid gap-3 mt-4">
                        <form method="POST" action="<?php echo e(route('admin.transactions.approve', $transaction)); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                <i class="bi bi-check2-circle me-2"></i>Tasdiqlash
                            </button>
                        </form>

                        <form method="POST" action="<?php echo e(route('admin.transactions.reject', $transaction)); ?>" onsubmit="return confirm('Tranzaksiyani rad etishga ishonchingiz komilmi?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <button type="submit" class="btn btn-danger w-100 rounded-pill">
                                <i class="bi bi-x-circle me-2"></i>Rad etish
                            </button>
                        </form>
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
    </div>

    <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Sellerning yaqin tranzaksiyalari','meta' => $sellerTransactions->count() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Sellerning yaqin tranzaksiyalari','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sellerTransactions->count() . ' ta yozuv')]); ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Miqdor</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Ko‘rish</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $sellerTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>#TRX-<?php echo e($row->id); ?></td>
                            <td class="font-monospace"><?php echo e(number_format((float) $row->amount, 0, '.', ' ')); ?> UZS</td>
                            <td><span class="badge rounded-pill text-bg-light border"><?php echo e($row->status_label); ?></span></td>
                            <td><?php echo e(optional($row->created_at)->format('d.m.Y H:i')); ?></td>
                            <td class="text-end">
                                <a href="<?php echo e(route('admin.transactions.show', $row)); ?>" class="btn btn-sm btn-dark rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i>Ko‘rish
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">Boshqa tranzaksiyalar topilmadi.</td>
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/transactions/show.blade.php ENDPATH**/ ?>