<?php $__env->startSection('title', 'Mystery Box tariflar'); ?>
<?php $__env->startSection('page-title', 'Mystery Box'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Mystery Box tariflar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Foydalanuvchilarga ko'rsatiladigan obuna rejalari <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('admin.mystery-box.subscriptions')); ?>" class="btn-p ghost">
        <i class="bi bi-list-ul"></i> Obunalar
      </a>
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


<div class="grid grid-cols-1 xl:grid-cols-12 gap-3 items-start">

  
  <div class="xl:col-span-8">
    <?php $__empty_1 = true; $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div>
          <div class="p-card-title"><?php echo e($plan->name_uz); ?></div>
          <?php if($plan->name_ru): ?>
          <div style="font-size:11px;color:var(--p-hint)"><?php echo e($plan->name_ru); ?></div>
          <?php endif; ?>
          <?php if($plan->name_en): ?>
          <div style="font-size:11px;color:var(--p-hint)"><?php echo e($plan->name_en); ?></div>
          <?php endif; ?>
          <?php if($plan->name_ja): ?>
          <div style="font-size:11px;color:var(--p-hint)"><?php echo e($plan->name_ja); ?></div>
          <?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
          <span class="s-pill <?php echo e($plan->is_active ? 'success' : 'muted'); ?>" style="font-size:10px">
            <?php echo e($plan->is_active ? 'Faol' : 'Nofaol'); ?>

          </span>
          <span class="s-pill muted" style="font-size:10px">
            <?php echo e($plan->subscriptions_count); ?> ta obuna
          </span>
        </div>
      </div>

      <form method="POST" action="<?php echo e(route('admin.mystery-box.plans.update', $plan)); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Nomi (UZ) *</label>
              <input type="text" name="name_uz" class="p-form-control"
                     value="<?php echo e($plan->name_uz); ?>" required>
            </div>
            <div class="">
              <label class="p-form-label">Nomi (RU)</label>
              <input type="text" name="name_ru" class="p-form-control"
                     value="<?php echo e($plan->name_ru); ?>">
            </div>
            <div class="">
              <label class="p-form-label">Nomi (EN)</label>
              <input type="text" name="name_en" class="p-form-control"
                     value="<?php echo e($plan->name_en); ?>">
            </div>
            <div class="">
              <label class="p-form-label">Nomi (JA)</label>
              <input type="text" name="name_ja" class="p-form-control"
                     value="<?php echo e($plan->name_ja); ?>">
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Muddat (oy)</label>
              <input type="number" class="p-form-control"
                     value="<?php echo e($plan->months); ?>" disabled
                     style="background:var(--p-elevated);cursor:not-allowed">
              <div style="font-size:10px;color:var(--p-hint);margin-top:3px">
                O'zgartirib bo'lmaydi
              </div>
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Har oyda (kitob) *</label>
              <input type="number" name="books_per_month" class="p-form-control"
                     value="<?php echo e($plan->books_per_month); ?>" min="1" max="10" required>
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Narxi (UZS) *</label>
              <input type="number" name="price_uzs" class="p-form-control"
                     value="<?php echo e($plan->price_uzs); ?>" min="1000" required>
              <div style="font-size:10px;color:var(--p-hint);margin-top:3px">
                Oyiga: <?php echo e(number_format((int)($plan->price_uzs / $plan->months))); ?> UZS
              </div>
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Tartib</label>
              <input type="number" name="sort_order" class="p-form-control"
                     value="<?php echo e($plan->sort_order); ?>" min="0">
            </div>
            <div class="">
              <label class="p-form-label">Tavsif</label>
              <textarea name="description_uz" class="p-form-control"
                        rows="2"><?php echo e($plan->description_uz); ?></textarea>
            </div>
            <div class="">
              <label class="p-form-label">Tavsif (RU)</label>
              <textarea name="description_ru" class="p-form-control"
                        rows="2"><?php echo e($plan->description_ru); ?></textarea>
            </div>
            <div class="">
              <label class="p-form-label">Tavsif (EN)</label>
              <textarea name="description_en" class="p-form-control"
                        rows="2"><?php echo e($plan->description_en); ?></textarea>
            </div>
            <div class="">
              <label class="p-form-label">Tavsif (JA)</label>
              <textarea name="description_ja" class="p-form-control"
                        rows="2"><?php echo e($plan->description_ja); ?></textarea>
            </div>
            <div class="">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       <?php echo e($plan->is_active ? 'checked' : ''); ?>

                       style="width:16px;height:16px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Faol (foydalanuvchilarga ko'rinadi)</span>
              </label>
            </div>
          </div>

          <div class="flex gap-2 mt-3">
            <button type="submit" class="btn-p primary">
              <i class="bi bi-check-lg"></i> Saqlash
            </button>
            <?php if($plan->subscriptions_count === 0): ?>
            <button type="button"
                    onclick="if(confirm('O\'chirilsinmi?')) document.getElementById('del<?php echo e($plan->id); ?>').submit()"
                    class="btn-p danger ghost sm">
              <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
      </form>

      <?php if($plan->subscriptions_count === 0): ?>
      <form id="del<?php echo e($plan->id); ?>" method="POST"
            action="<?php echo e(route('admin.mystery-box.plans.destroy', $plan)); ?>" style="display:none">
        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="p-card fade-up" style="text-align:center;padding:40px;color:var(--p-hint)">
      <i class="bi bi-box" style="font-size:32px;display:block;margin-bottom:8px"></i>
      Hali tariflar yo'q. O'ngdagi formadan birinchi tarifni yarating.
    </div>
    <?php endif; ?>
  </div>

  
  <div class="xl:col-span-4">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-plus-lg mr-1" style="color:var(--p-accent)"></i>
          Yangi tarif
        </div>
      </div>
      <form method="POST" action="<?php echo e(route('admin.mystery-box.plans.store')); ?>">
        <?php echo csrf_field(); ?>
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Nomi (UZ) *</label>
              <input type="text" name="name_uz" class="p-form-control"
                     placeholder="Masalan: 3 oylik obuna" required>
            </div>
            <div class="">
              <label class="p-form-label">Nomi (RU)</label>
              <input type="text" name="name_ru" class="p-form-control"
                     placeholder="Masalan: Подписка на 3 месяца">
            </div>
            <div class="">
              <label class="p-form-label">Nomi (EN)</label>
              <input type="text" name="name_en" class="p-form-control"
                     placeholder="For example: 3-month subscription">
            </div>
            <div class="">
              <label class="p-form-label">Nomi (JA)</label>
              <input type="text" name="name_ja" class="p-form-control"
                     placeholder="例: 3か月プラン">
            </div>
            <div class="w-1/2">
              <label class="p-form-label">Muddat (oy) *</label>
              <select name="months" class="p-form-control" required>
                <option value="1">1 oy</option>
                <option value="3">3 oy</option>
                <option value="6">6 oy</option>
                <option value="12">12 oy</option>
              </select>
            </div>
            <div class="w-1/2">
              <label class="p-form-label">Kitob/oy *</label>
              <input type="number" name="books_per_month" class="p-form-control"
                     value="2" min="1" max="10" required>
            </div>
            <div class="">
              <label class="p-form-label">Narxi (UZS) *</label>
              <input type="number" name="price_uzs" class="p-form-control"
                     placeholder="350000" min="1000" required>
            </div>
            <div class="w-1/2">
              <label class="p-form-label">Tartib</label>
              <input type="number" name="sort_order" class="p-form-control"
                     value="<?php echo e($plans->max('sort_order') + 1); ?>">
            </div>
            <div class="">
              <label class="p-form-label">Tavsif</label>
              <textarea name="description_uz" class="p-form-control"
                        rows="2" placeholder="Qisqa tavsif..."></textarea>
            </div>
            <div class="">
              <label class="p-form-label">Tavsif (RU)</label>
              <textarea name="description_ru" class="p-form-control"
                        rows="2" placeholder="Краткое описание..."></textarea>
            </div>
            <div class="">
              <label class="p-form-label">Tavsif (EN)</label>
              <textarea name="description_en" class="p-form-control"
                        rows="2" placeholder="Short description..."></textarea>
            </div>
            <div class="">
              <label class="p-form-label">Tavsif (JA)</label>
              <textarea name="description_ja" class="p-form-control"
                        rows="2" placeholder="簡単な説明..."></textarea>
            </div>
          </div>
          <button type="submit" class="btn-p primary mt-3" style="width:100%;justify-content:center">
            <i class="bi bi-plus-lg"></i> Tarif yaratish
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/mystery-box/plans.blade.php ENDPATH**/ ?>