<?php $__env->startSection('title', 'Post tahrirlash #'.$bookClub->id); ?>
<?php $__env->startSection('page-title', 'Post tahrirlash'); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-page-inner w-full min-w-0">
    <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.book-club.show', $bookClub)).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.book-club.show', $bookClub)).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Post #<?php echo e($bookClub->id); ?> tahrirlash <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Muallif:
          <a href="<?php echo e(route('admin.users.show', $bookClub->user_id)); ?>"
             style="color:var(--p-accent)">
            <?php echo e($bookClub->user?->name); ?> <?php echo e($bookClub->user?->lastname); ?>

          </a> <?php $__env->endSlot(); ?>
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


    <form method="POST" action="<?php echo e(route('admin.book-club.update', $bookClub)); ?>">
      <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

      
      <div class="p-card mb-3 fade-up">
        <div class="dash-card-head"><div class="dash-card-title">Post matni</div></div>
        <div class="dash-card-body">
          <textarea name="text" rows="6"
                    class="p-form-control <?php $__errorArgs = ['text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    required maxlength="2000"
                    placeholder="Post matni..."><?php echo e(old('text', $bookClub->text)); ?></textarea>
          <?php $__errorArgs = ['text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          <div style="font-size:11px;color:var(--p-hint);margin-top:4px;text-align:right">
            <?php echo e(strlen($bookClub->text)); ?> / 2000
          </div>
        </div>
      </div>

      
      <?php if($bookClub->images->count()): ?>
      <div class="p-card mb-3 fade-up">
        <div class="dash-card-head">
          <div class="dash-card-title">Rasmlar</div>
          <div class="dash-card-sub">O'chirish uchun X bosing</div>
        </div>
        <div class="dash-card-body">
          <div class="flex flex-wrap gap-2">
            <?php $__currentLoopData = $bookClub->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="position:relative">
              <img src="<?php echo e(asset('storage/'.$img->image)); ?>"
                   style="width:90px;height:90px;border-radius:8px;object-fit:cover;
                          border:1px solid var(--p-border)">
              <form method="POST"
                    action="<?php echo e(route('admin.book-club.image.delete', $img)); ?>"
                    style="position:absolute;top:4px;right:4px"
                    onsubmit="return confirm('Rasm o\'chirilsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button style="width:22px;height:22px;border-radius:50%;
                               background:rgba(0,0,0,.65);border:none;color:#fff;
                               font-size:10px;cursor:pointer;
                               display:flex;align-items:center;justify-content:center">
                  <i class="bi bi-x"></i>
                </button>
              </form>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      
      <?php if($bookClub->votes->count()): ?>
      <div class="p-card mb-3 fade-up" style="opacity:.7">
        <div class="dash-card-head">
          <div class="dash-card-title">So'rovnoma</div>
          <div class="dash-card-sub"><i class="bi bi-lock"></i> Faqat ko'rish</div>
        </div>
        <div class="dash-card-body">
          <?php $__currentLoopData = $bookClub->votes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div style="background:var(--p-elevated);border-radius:7px;padding:10px 14px;margin-bottom:8px;
                      font-size:13px;color:var(--p-text)">
            <?php echo e($vote->option_text); ?>

          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
      <?php endif; ?>

      
      <div style="padding:12px 16px;background:var(--p-warning-d);border-radius:8px;
                  border:1px solid rgba(245,166,35,.2);margin-bottom:20px" class="fade-up">
        <div style="font-size:12px;color:var(--p-warning);display:flex;gap:8px">
          <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0"></i>
          Faqat matnni tahrirlash mumkin. Rasmlar va so'rovnomalar alohida boshqariladi.
        </div>
      </div>

      <div class="flex gap-2 justify-end fade-up">
        <a href="<?php echo e(route('admin.book-club.show', $bookClub)); ?>" class="btn-p ghost">
          Bekor qilish
        </a>
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
      </div>
    </form>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/book-club/edit.blade.php ENDPATH**/ ?>