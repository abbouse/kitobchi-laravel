<?php $__env->startSection('title', 'Book Club · UGC navbati'); ?>
<?php $__env->startSection('page-title', 'Kangaroo: admin navbati'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.book-club.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.book-club.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> UGC navbati <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> 
    Kangaroo past ishonch yoki bahosiz qoldirgan post va izohlarni 1–5 yulduz bilan baholang.
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

<div class="grid grid-cols-1 gap-3">

  <div class="p-card fade-up">
    <div class="dash-card-head">
      <div class="dash-card-title">Post matni (kutilmoqda)</div>
      <div class="dash-card-sub"><?php echo e($pendingPosts->count()); ?> ta</div>
    </div>
    <div class="dash-card-body p-0">
      <?php $__empty_1 = true; $__currentLoopData = $pendingPosts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">
          <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
            <div>
              <a href="<?php echo e(route('admin.book-club.show', $row)); ?>" class="font-semibold" style="color:var(--p-text);text-decoration:none">
                Post #<?php echo e($row->id); ?>

              </a>
              <span style="font-size:12px;color:var(--p-hint);margin-left:8px">
                <?php echo e($row->created_at?->format('d.m.Y H:i')); ?>

              </span>
              <?php if($row->user): ?>
                <div style="font-size:12px;color:var(--p-muted);margin-top:4px">
                  <?php echo e($row->user->name); ?> <?php echo e($row->user->lastname); ?>

                </div>
              <?php endif; ?>
            </div>
            <span class="btn-p ghost sm" style="pointer-events:none;border-color:var(--p-warning);color:var(--p-warning)">
              <i class="bi bi-hourglass-split"></i> pending_admin
            </span>
          </div>
          <?php if($row->text): ?>
            <p style="font-size:13px;color:var(--p-text);line-height:1.6;white-space:pre-line;margin-bottom:12px">
              <?php echo e(\Illuminate\Support\Str::limit($row->text, 400)); ?>

            </p>
          <?php else: ?>
            <p style="font-size:12px;color:var(--p-hint);margin-bottom:12px">Matn yo‘q (faqat rasm/vote bo‘lishi mumkin).</p>
          <?php endif; ?>
          <form method="POST" action="<?php echo e(route('admin.book-club.post-ugc-score', $row)); ?>" class="flex flex-wrap items-end gap-2">
            <?php echo csrf_field(); ?>
            <label class="text-xs" style="color:var(--p-hint)">Bahosi (1–5)</label>
            <select name="star" class="p-form-control" style="width:90px" required>
              <?php for($s = 1; $s <= 5; $s++): ?>
                <option value="<?php echo e($s); ?>"><?php echo e($s); ?> ★</option>
              <?php endfor; ?>
            </select>
            <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
            <a href="<?php echo e(route('admin.book-club.show', $row)); ?>" class="btn-p ghost sm">Post sahifasi</a>
          </form>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:28px;color:var(--p-hint)">
          Kutilayotgan post yo‘q
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="p-card fade-up">
    <div class="dash-card-head">
      <div class="dash-card-title">Asosiy izohlar (kutilmoqda)</div>
      <div class="dash-card-sub"><?php echo e($pendingComments->count()); ?> ta</div>
    </div>
    <div class="dash-card-body p-0">
      <?php $__empty_1 = true; $__currentLoopData = $pendingComments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">
          <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
            <div>
              <?php if($c->post): ?>
                <a href="<?php echo e(route('admin.book-club.show', $c->post)); ?>" style="font-size:13px;font-weight:600;color:var(--p-accent);text-decoration:none">
                  Post #<?php echo e($c->post_id); ?>

                </a>
              <?php else: ?>
                <span style="font-size:13px;font-weight:600">Post #<?php echo e($c->post_id); ?></span>
              <?php endif; ?>
              <span style="font-size:12px;color:var(--p-hint);margin-left:8px">
                <?php echo e($c->created_at?->format('d.m.Y H:i')); ?>

              </span>
            </div>
            <span class="btn-p ghost sm" style="pointer-events:none;border-color:var(--p-warning);color:var(--p-warning)">
              <i class="bi bi-hourglass-split"></i> pending_admin
            </span>
          </div>
          <?php if($c->user): ?>
            <div style="font-size:12px;color:var(--p-muted);margin-bottom:6px">
              <?php echo e($c->user->name); ?> <?php echo e($c->user->lastname); ?>

            </div>
          <?php endif; ?>
          <p style="font-size:13px;color:var(--p-text);line-height:1.6;white-space:pre-line;margin-bottom:12px">
            <?php echo e(\Illuminate\Support\Str::limit($c->content, 500)); ?>

          </p>
          <?php if($c->kangaroo_toxicity !== null): ?>
            <div style="font-size:11px;color:var(--p-hint);margin-bottom:8px">
              Toxicity (Kangaroo): <?php echo e(number_format((float) $c->kangaroo_toxicity, 3)); ?>

            </div>
          <?php endif; ?>
          <form method="POST" action="<?php echo e(route('admin.book-club.comment.ugc-score', $c)); ?>" class="flex flex-wrap items-end gap-2">
            <?php echo csrf_field(); ?>
            <label class="text-xs" style="color:var(--p-hint)">Bahosi (1–5)</label>
            <select name="star" class="p-form-control" style="width:90px" required>
              <?php for($s = 1; $s <= 5; $s++): ?>
                <option value="<?php echo e($s); ?>"><?php echo e($s); ?> ★</option>
              <?php endfor; ?>
            </select>
            <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
            <?php if($c->post): ?>
              <a href="<?php echo e(route('admin.book-club.show', $c->post)); ?>" class="btn-p ghost sm">Post sahifasi</a>
            <?php endif; ?>
          </form>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:28px;color:var(--p-hint)">
          Kutilayotgan izoh yo‘q
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/book-club/moderation-queue.blade.php ENDPATH**/ ?>