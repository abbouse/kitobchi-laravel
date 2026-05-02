<?php $__env->startSection('title', "Reklama #{$ad->id}"); ?>

<?php $__env->startSection('content'); ?>
<?php
    $adModeration = $ad->moderation ?: 'pending';
    $adPayment = $ad->paymentStatus ?: 'pending';
?>
<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.ads.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.ads.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> Reklama #<?php echo e($ad->id); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Banner, bog'langan obyekt va moderatsiya holati <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
        <?php if($ad->moderation === 'pending'): ?>
            <form method="POST" action="<?php echo e(route('admin.ads.moderate', $ad)); ?>" class="inline-flex">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="action" value="approve">
                <button class="btn-p success" type="submit"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
            </form>
            <form method="POST" action="<?php echo e(route('admin.ads.moderate', $ad)); ?>" class="inline-flex">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="action" value="reject">
                <button class="btn-p danger" type="submit"><i class="bi bi-x-lg"></i> Rad etish</button>
            </form>
        <?php endif; ?>
     <?php $__env->endSlot(); ?>
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

<section class="a122-section mb-4">
    <div class="a122-section-body">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="kpi-soft">
                <div class="metric-label">Moderatsiya</div>
                <div class="metric-value text-xl"><?php echo e(ucfirst($adModeration)); ?></div>
                <div class="metric-meta">Admin review holati</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">To‘lov</div>
                <div class="metric-value text-xl"><?php echo e(ucfirst($adPayment)); ?></div>
                <div class="metric-meta">Payment bosqichi</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Budjet</div>
                <div class="metric-value text-xl"><?php echo e(number_format((int) $ad->amount)); ?></div>
                <div class="metric-meta">UZS</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Muddat</div>
                <div class="metric-value text-xl"><?php echo e($ad->days ?: '—'); ?></div>
                <div class="metric-meta">Kun</div>
            </div>
        </div>
    </div>
</section>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-8 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-megaphone mr-2" style="color:var(--p-accent)"></i>Banner preview</div>
                    <div class="p-card-sub">Reklama materiali va tavsifi</div>
                </div>
            </div>

            <?php if($ad->banner_img): ?>
                <a href="<?php echo e($ad->banner_img); ?>" target="_blank" rel="noopener">
                    <img src="<?php echo e($ad->banner_img); ?>" alt="Ad banner" style="width:100%;max-height:380px;object-fit:cover;border-radius:18px;border:1px solid var(--p-border)">
                </a>
            <?php else: ?>
                <div class="hero-panel" style="min-height:240px;display:flex;align-items:center;justify-content:center">
                    <div style="text-align:center;color:var(--p-hint)">
                        <i class="bi bi-image" style="font-size:44px;display:block;margin-bottom:8px"></i>
                        Banner rasmi biriktirilmagan
                    </div>
                </div>
            <?php endif; ?>

            <div class="content-prose" style="margin-top:18px">
                <h3>Tavsif</h3>
                <p><?php echo e($ad->description ?: 'Reklama uchun tavsif kiritilmagan.'); ?></p>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-box-seam mr-2" style="color:var(--p-info)"></i>Bog'langan obyekt</div>
                    <div class="p-card-sub">Reklama bosilganda foydalanuvchi qayerga yo'naltiriladi</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="data-kv">
                    <div class="label">Action</div>
                    <div class="value"><?php echo e($ad->action ?: '—'); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Mahsulot turi</div>
                    <div class="value"><?php echo e($ad->product_type ?: '—'); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Mahsulot ID</div>
                    <div class="value">#<?php echo e($ad->product_id ?: '—'); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Reklama turi</div>
                    <div class="value"><?php echo e($ad->type ?: '—'); ?></div>
                </div>
            </div>

            <div class="mt-4">
                <?php if($product): ?>
                    <div class="module-link-card">
                        <div>
                            <div class="module-link-card__title"><?php echo e($product->title ?? $product->name ?? ('#'.$product->id)); ?></div>
                            <div class="module-link-card__meta">Bog'langan obyekt topildi va reklamaga ulangan.</div>
                        </div>
                        <div class="s-pill accent">ID <?php echo e($product->id); ?></div>
                    </div>
                <?php else: ?>
                    <div class="p-quote-block">
                        Bog'langan obyekt hozircha topilmadi yoki model turi resolve bo'lmadi.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="xl:col-span-4 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-activity mr-2" style="color:var(--p-success)"></i>Status overview</div>
            </div>
            <div class="space-y-3">
                <div class="data-kv">
                    <div class="label">Moderatsiya</div>
                    <div class="value">
                        <span class="s-pill <?php echo e($ad->moderation === 'approved' ? 'success' : ($ad->moderation === 'rejected' ? 'danger' : 'warning')); ?>">
                            <?php echo e($ad->moderation); ?>

                        </span>
                    </div>
                </div>
                <div class="data-kv">
                    <div class="label">To'lov</div>
                    <div class="value">
                        <span class="s-pill <?php echo e($ad->paymentStatus === 'paid' ? 'success' : ($ad->paymentStatus === 'failed' ? 'danger' : 'warning')); ?>">
                            <?php echo e($ad->paymentStatus ?: 'pending'); ?>

                        </span>
                    </div>
                </div>
                <div class="data-kv">
                    <div class="label">Summa</div>
                    <div class="value"><?php echo e(number_format((int) $ad->amount)); ?> UZS</div>
                </div>
                <div class="data-kv">
                    <div class="label">Kun soni</div>
                    <div class="value"><?php echo e($ad->days ?: '—'); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Tugash sanasi</div>
                    <div class="value"><?php echo e(optional($ad->expire_at)?->format('d.m.Y H:i') ?: '—'); ?></div>
                </div>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-shop mr-2" style="color:var(--p-warning)"></i>Seller</div>
            </div>
            <div class="space-y-3">
                <div class="data-kv">
                    <div class="label">Seller ID</div>
                    <div class="value">#<?php echo e($ad->seller_id); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Do'kon</div>
                    <div class="value"><?php echo e($ad->seller->shop_name ?? 'Noma\'lum seller'); ?></div>
                </div>
                <?php if($ad->seller): ?>
                    <a href="<?php echo e(route('admin.sellers.show', $ad->seller)); ?>" class="btn-p ghost" style="width:100%;justify-content:center">
                        <i class="bi bi-arrow-right-circle"></i> Seller profilini ochish
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-clock-history mr-2" style="color:var(--p-muted)"></i>Timeline</div>
            </div>
            <div class="space-y-3">
                <?php $__currentLoopData = $timeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="data-kv">
                        <div class="label"><?php echo e($item['label']); ?></div>
                        <div class="value"><?php echo e($item['value'] ?: '—'); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/ads/show.blade.php ENDPATH**/ ?>