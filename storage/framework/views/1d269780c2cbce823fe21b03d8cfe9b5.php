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

  <div class="a122-section fade-up">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Post matni (kutilmoqda)</div>
        <div class="a122-section-head__meta"><?php echo e($pendingPosts->count()); ?> ta post admin bahosini kutmoqda.</div>
      </div>
      <?php if($pendingPosts->count()): ?>
      <div class="flex flex-wrap items-center gap-2">
        <label class="flex items-center gap-2 text-xs" style="color:var(--p-hint);cursor:pointer">
          <input type="checkbox" id="select-all-posts">
          Hammasini belgilash
        </label>
      </div>
      <?php endif; ?>
    </div>
    <div class="a122-section-body p-0">
      <?php if($pendingPosts->count()): ?>
      <form method="POST" action="<?php echo e(route('admin.book-club.bulk-post-ugc-score')); ?>" id="bulk-post-form">
        <?php echo csrf_field(); ?>
        <div class="flex flex-wrap items-end gap-2" style="padding:14px 20px;border-bottom:1px solid var(--p-border);background:var(--p-elevated)">
          <div>
            <label class="text-xs" style="color:var(--p-hint)">Tanlangan postlar bahosi</label>
            <select name="star" class="p-form-control" style="width:110px" required>
              <?php for($s = 1; $s <= 5; $s++): ?>
                <option value="<?php echo e($s); ?>"><?php echo e($s); ?> ★</option>
              <?php endfor; ?>
            </select>
          </div>
          <button type="submit" class="btn-p primary sm"><i class="bi bi-check2-square"></i> Tanlanganni saqlash</button>
        </div>
      <?php endif; ?>
      <?php $__empty_1 = true; $__currentLoopData = $pendingPosts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">
          <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
            <div class="flex items-start gap-3">
              <input type="checkbox" name="post_ids[]" value="<?php echo e($row->id); ?>" form="bulk-post-form" class="bulk-post-checkbox" style="margin-top:4px">
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
      <?php if($pendingPosts->count()): ?>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="a122-section fade-up">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Izohlar (kutilmoqda)</div>
        <div class="a122-section-head__meta"><?php echo e($pendingComments->count()); ?> ta izoh admin bahosini kutmoqda.</div>
      </div>
      <?php if($pendingComments->count()): ?>
      <div class="flex flex-wrap items-center gap-2">
        <label class="flex items-center gap-2 text-xs" style="color:var(--p-hint);cursor:pointer">
          <input type="checkbox" id="select-all-comments">
          Hammasini belgilash
        </label>
      </div>
      <?php endif; ?>
    </div>
    <div class="a122-section-body p-0">
      <?php if($pendingComments->count()): ?>
      <form method="POST" action="<?php echo e(route('admin.book-club.bulk-comment.ugc-score')); ?>" id="bulk-comment-form">
        <?php echo csrf_field(); ?>
        <div class="flex flex-wrap items-end gap-2" style="padding:14px 20px;border-bottom:1px solid var(--p-border);background:var(--p-elevated)">
          <div>
            <label class="text-xs" style="color:var(--p-hint)">Tanlangan izohlar bahosi</label>
            <select name="star" class="p-form-control" style="width:110px" required>
              <?php for($s = 1; $s <= 5; $s++): ?>
                <option value="<?php echo e($s); ?>"><?php echo e($s); ?> ★</option>
              <?php endfor; ?>
            </select>
          </div>
          <button type="submit" class="btn-p primary sm"><i class="bi bi-check2-square"></i> Tanlanganni saqlash</button>
        </div>
      <?php endif; ?>
      <?php $__empty_1 = true; $__currentLoopData = $pendingComments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">
          <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
            <div class="flex items-start gap-3">
              <input type="checkbox" name="comment_ids[]" value="<?php echo e($c->id); ?>" form="bulk-comment-form" class="bulk-comment-checkbox" style="margin-top:4px">
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
              <?php if($c->parent): ?>
                <div style="font-size:12px;color:var(--p-hint);margin-top:4px">
                  Javob izoh · <?php echo e(\Illuminate\Support\Str::limit($c->parent->content, 90)); ?>

                </div>
              <?php endif; ?>
              </div>
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
      <?php if($pendingComments->count()): ?>
      </form>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
  document.getElementById('select-all-posts')?.addEventListener('change', function () {
    document.querySelectorAll('.bulk-post-checkbox').forEach((checkbox) => {
      checkbox.checked = this.checked;
    });
  });

  document.getElementById('select-all-comments')?.addEventListener('change', function () {
    document.querySelectorAll('.bulk-comment-checkbox').forEach((checkbox) => {
      checkbox.checked = this.checked;
    });
  });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/book-club/moderation.blade.php ENDPATH**/ ?>