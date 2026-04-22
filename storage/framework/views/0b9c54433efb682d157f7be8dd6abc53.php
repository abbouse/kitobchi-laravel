

<?php $showUser = $showUser ?? true; ?>

<div class="p-card fade-up" style="height:100%">
  <div class="dash-card-body">

    
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center gap-3">
        <?php if($showUser && $post->user): ?>
        <a href="<?php echo e(route('admin.users.show', $post->user_id)); ?>"
           style="display:block;width:38px;height:38px;border-radius:50%;overflow:hidden;
                  background:linear-gradient(135deg,var(--p-accent),#7c5cfc);flex-shrink:0">
          <?php if($post->user->avatar): ?>
            <img src="<?php echo e($post->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                        font-size:14px;font-weight:700;color:#fff">
              <?php echo e(strtoupper(substr($post->user->name ?? 'U', 0, 1))); ?>

            </div>
          <?php endif; ?>
        </a>
        <div>
          <a href="<?php echo e(route('admin.users.show', $post->user_id)); ?>"
             style="font-size:13px;font-weight:600;color:var(--p-text);text-decoration:none">
            <?php echo e($post->user->name); ?> <?php echo e($post->user->lastname); ?>

          </a>
          <div style="font-size:11px;color:var(--p-hint)">
            <?php echo e($post->created_at?->diffForHumans()); ?>

            <?php if($post->is_repost ?? $post->repost): ?>
              · <span style="color:var(--p-info)"><i class="bi bi-repeat"></i> Repost</span>
            <?php endif; ?>
          </div>
        </div>
        <?php else: ?>
        <div style="font-size:12px;color:var(--p-hint)">
          <?php echo e($post->created_at?->diffForHumans()); ?>

        </div>
        <?php endif; ?>
      </div>

      
      <div class="flex gap-1">
        <a href="<?php echo e(route('admin.book-club.show', $post)); ?>" class="btn-p ghost sm">
          <i class="bi bi-eye"></i>
        </a>
        <a href="<?php echo e(route('admin.book-club.edit', $post)); ?>" class="btn-p ghost sm">
          <i class="bi bi-pencil"></i>
        </a>
        <form method="POST" action="<?php echo e(route('admin.book-club.destroy', $post)); ?>"
              onsubmit="return confirm('Post o\'chirilsinmi?')">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
          <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
        </form>
      </div>
    </div>

    
    <?php if($post->text): ?>
    <p style="font-size:14px;color:var(--p-text);line-height:1.7;margin-bottom:12px;
              white-space:pre-line"><?php echo e(Str::limit($post->text, 200)); ?></p>
    <?php endif; ?>

    
    <?php if($post->images && $post->images->count()): ?>
    <div class="flex flex-wrap gap-2 mb-12" style="margin-bottom:12px">
      <?php $__currentLoopData = $post->images->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(asset('storage/'.$img->image)); ?>" target="_blank"
         style="width:80px;height:80px;border-radius:8px;overflow:hidden;display:block;
                background:var(--p-elevated);flex-shrink:0">
        <img src="<?php echo e(asset('storage/'.$img->image)); ?>"
             style="width:100%;height:100%;object-fit:cover">
      </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php if($post->images->count() > 4): ?>
        <div style="width:80px;height:80px;border-radius:8px;background:var(--p-elevated);
                    display:flex;align-items:center;justify-content:center;font-size:13px;
                    color:var(--p-hint)">+<?php echo e($post->images->count() - 4); ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    
    <?php if($post->product_id): ?>
    <div style="display:inline-flex;align-items:center;gap:6px;background:var(--p-elevated);
                border-radius:8px;padding:6px 10px;font-size:12px;color:var(--p-muted);margin-bottom:10px">
      <i class="bi bi-<?php echo e($post->product_type === 'book' ? 'book' : 'pencil-square'); ?>"
         style="color:var(--p-accent)"></i>
      <?php echo e($post->product_type === 'book' ? 'Kitob' : 'Kanstovar'); ?> #<?php echo e($post->product_id); ?>

    </div>
    <?php endif; ?>

    
    <div class="flex items-center gap-3 mt-2"
         style="padding-top:10px;border-top:1px solid var(--p-border)">
      <span style="font-size:12px;color:var(--p-muted);display:flex;align-items:center;gap:4px">
        <i class="bi bi-heart" style="color:var(--p-danger)"></i>
        <?php echo e(number_format($post->likes_count ?? 0)); ?>

      </span>
      <span style="font-size:12px;color:var(--p-muted);display:flex;align-items:center;gap:4px">
        <i class="bi bi-chat" style="color:var(--p-accent)"></i>
        <?php echo e(number_format($post->comments_count ?? 0)); ?>

      </span>
      <?php if($post->votes && $post->votes->count()): ?>
      <span style="font-size:12px;color:var(--p-muted);display:flex;align-items:center;gap:4px">
        <i class="bi bi-bar-chart" style="color:var(--p-info)"></i>
        So'rovnoma · <?php echo e($post->votes->count()); ?> variant
      </span>
      <?php endif; ?>
      <span style="font-size:11px;color:var(--p-hint);margin-left:auto">
        #<?php echo e($post->id); ?>

      </span>
    </div>

  </div>
</div><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/book-club/_post-card.blade.php ENDPATH**/ ?>