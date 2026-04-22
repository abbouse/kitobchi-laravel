<?php $__env->startSection('title', $news->title); ?>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.news.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.news.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($news->title); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Bozor yangiligi, banner va target action tafsilotlari <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
        <form method="POST" action="<?php echo e(route('admin.news.toggle', $news)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <button type="submit" class="btn-p <?php echo e($news->status ? 'ghost' : 'primary'); ?>">
                <i class="bi <?php echo e($news->status ? 'bi-eye-slash' : 'bi-eye'); ?>"></i>
                <?php echo e($news->status ? 'Yashirish' : 'Faollashtirish'); ?>

            </button>
        </form>
        <a href="<?php echo e(route('admin.news.edit', $news)); ?>" class="btn-p primary">
            <i class="bi bi-pencil"></i> Tahrirlash
        </a>
        <form method="POST" action="<?php echo e(route('admin.news.destroy', $news)); ?>" onsubmit="return confirm('Bu yangilik o\'chirilsinmi?')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn-p danger">
                <i class="bi bi-trash"></i> O'chirish
            </button>
        </form>
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

<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-8 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-image mr-2" style="color:var(--p-accent)"></i>Banner va kontent</div>
                    <div class="p-card-sub">Foydalanuvchi ilovasida ko‘rinadigan asosiy blok</div>
                </div>
                <span class="s-pill <?php echo e($news->status ? 'success' : 'danger'); ?>">
                    <?php echo e($news->status ? 'Faol' : 'Yashirin'); ?>

                </span>
            </div>

            <?php if($news->imgUrl): ?>
                <img
                    src="<?php echo e(Str::startsWith($news->imgUrl, 'http') ? $news->imgUrl : asset('storage/' . $news->imgUrl)); ?>"
                    alt="<?php echo e($news->title); ?>"
                    style="width:100%;max-height:380px;object-fit:cover;border-radius:18px;border:1px solid var(--p-border)"
                >
            <?php else: ?>
                <div class="hero-panel" style="min-height:220px;display:flex;align-items:center;justify-content:center">
                    <div style="text-align:center;color:var(--p-hint)">
                        <i class="bi bi-card-image" style="font-size:42px;display:block;margin-bottom:8px"></i>
                        Banner rasmi yuklanmagan
                    </div>
                </div>
            <?php endif; ?>

            <div class="content-prose" style="margin-top:18px">
                <h3>Tavsif</h3>
                <p><?php echo e($news->description ?: 'Tavsif kiritilmagan.'); ?></p>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-cursor mr-2" style="color:var(--p-info)"></i>Action target</div>
                    <div class="p-card-sub">Yangilik bosilganda foydalanuvchi qayerga o‘tadi</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="data-kv">
                    <div class="label">Action</div>
                    <div class="value"><?php echo e($news->action_label); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Action kodi</div>
                    <div class="value"><?php echo e($news->action); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Action ID</div>
                    <div class="value"><?php echo e($news->action_id ? '#'.$news->action_id : '—'); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Align</div>
                    <div class="value"><?php echo e($news->align ?: '—'); ?></div>
                </div>
            </div>

            <div class="mt-4">
                <?php if($target): ?>
                    <div class="module-link-card">
                        <div>
                            <div class="module-link-card__title"><?php echo e($target->shop_name ?? $target->name ?? ('#'.$target->id)); ?></div>
                            <div class="module-link-card__meta">Bog‘langan target topildi va action bilan mos.</div>
                        </div>
                        <div class="s-pill accent">ID <?php echo e($target->id); ?></div>
                    </div>
                <?php elseif($news->action !== 'news'): ?>
                    <div class="p-quote-block">
                        Action ID bor, lekin bog‘langan obyekt topilmadi. Bu odatda o‘chirilgan shop yoki mahsulotga ishora qiladi.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="xl:col-span-4 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-warning)"></i>Meta</div>
            </div>
            <div class="space-y-3">
                <div class="data-kv">
                    <div class="label">ID</div>
                    <div class="value">#<?php echo e($news->id); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Yaratilgan</div>
                    <div class="value"><?php echo e($news->created_at?->format('d.m.Y H:i') ?: '—'); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Yangilangan</div>
                    <div class="value"><?php echo e($news->updated_at?->format('d.m.Y H:i') ?: '—'); ?></div>
                </div>
                <div class="data-kv">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="s-pill <?php echo e($news->status ? 'success' : 'danger'); ?>">
                            <?php echo e($news->status ? 'Faol' : 'Yashirin'); ?>

                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-lightning-charge mr-2" style="color:var(--p-accent)"></i>Admin eslatma</div>
            </div>
            <div class="p-quote-block">
                `to_shop` va `to_product` actionlarida target doimo mavjud bo‘lishi kerak. Aks holda banner bosilganda foydalanuvchi oqimi uziladi.
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/news/show.blade.php ENDPATH**/ ?>