<?php $__env->startSection('title', $marketNews->title); ?>
<?php $__env->startSection('page-title', $marketNews->title); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="<?php echo e(route('panel.market-news.index')); ?>" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title"><?php echo e($marketNews->title); ?></h1>
      <p class="page-sub" style="display:flex;align-items:center;gap:8px">
        <?php echo e($marketNews->created_at?->format('d.m.Y H:i')); ?>

        <?php if($marketNews->status): ?>
          <span class="s-pill success" style="font-size:10px">Faol</span>
        <?php else: ?>
          <span class="s-pill danger" style="font-size:10px">Nofaol</span>
        <?php endif; ?>
      </p>
    </div>
  </div>
  <div class="d-flex gap-2">
    <form method="POST" action="<?php echo e(route('panel.market-news.toggle', $marketNews)); ?>">
      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
      <button class="btn-p <?php echo e($marketNews->status ? 'ghost' : 'success'); ?>">
        <i class="bi bi-<?php echo e($marketNews->status ? 'pause' : 'play'); ?>-fill"></i>
        <?php echo e($marketNews->status ? 'O\'chirish' : 'Faollashtirish'); ?>

      </button>
    </form>
    <a href="<?php echo e(route('panel.market-news.edit', $marketNews)); ?>" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
    <form method="POST" action="<?php echo e(route('panel.market-news.destroy', $marketNews)); ?>"
          onsubmit="return confirm('O\'chirilsinmi?')">
      <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
      <button class="btn-p danger"><i class="bi bi-trash"></i></button>
    </form>
  </div>
</div>

<div class="row g-3">

  
  <div class="col-xl-5">

    
    <?php if($marketNews->imgUrl): ?>
    <div class="p-card mb-3 fade-up" style="padding:0;overflow:hidden">
      <img src="<?php echo e(asset('storage/'.$marketNews->imgUrl)); ?>"
           style="width:100%;display:block;max-height:220px;object-fit:cover">
    </div>
    <?php endif; ?>

    
    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Tafsilotlar</div></div>
      <div style="padding:0 18px 14px">
        <?php
          $actionColor = match($marketNews->action){
            'to_shop'    => 'warning',
            'to_product' => 'info',
            default      => 'muted',
          };
        ?>
        <?php $__currentLoopData = [
          ['Joylashuv', $marketNews->align === 'top' ? '⬆ Yuqori' : '↔ O\'rta'],
          ['Status',    $marketNews->status ? '✅ Faol' : '⭕ Nofaol'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;
                    padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;
                    padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">Action</span>
          <span class="s-pill <?php echo e($actionColor); ?>" style="font-size:10px">
            <?php echo e($marketNews->action_label); ?>

          </span>
        </div>
        <?php if($marketNews->action_id): ?>
        <div style="display:flex;justify-content:space-between;padding:9px 0">
          <span style="font-size:12px;color:var(--p-hint)">Action ID</span>
          <span style="font-size:13px;font-weight:600;font-family:'DM Mono',monospace;
                       color:var(--p-accent)">#<?php echo e($marketNews->action_id); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  
  <div class="col-xl-7">

    
    <?php if($marketNews->description): ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Tavsif</div></div>
      <div style="padding:0 18px 18px;font-size:14px;color:var(--p-muted);line-height:1.8">
        <?php echo e($marketNews->description); ?>

      </div>
    </div>
    <?php endif; ?>

    
    <?php if($marketNews->action !== 'news' && $marketNews->action_id): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <?php if($marketNews->action === 'to_shop'): ?>
            <i class="bi bi-shop-window me-1" style="color:var(--p-warning)"></i>
            Do'kon preview
          <?php else: ?>
            <i class="bi bi-book me-1" style="color:var(--p-info)"></i>
            Kitob preview
          <?php endif; ?>
        </div>
        <span style="font-size:11px;color:var(--p-hint)">Flutter ichida shunday ko'rinadi</span>
      </div>
      <div style="padding:0 18px 18px">

        <?php if($marketNews->action === 'to_shop' && $preview): ?>
        
        <a href="<?php echo e(route('panel.sellers.show', $preview->id)); ?>"
           style="display:flex;align-items:center;gap:14px;padding:14px;
                  background:var(--p-elevated);border-radius:12px;
                  border:1px solid var(--p-border);text-decoration:none;
                  transition:border-color .15s"
           onmouseover="this.style.borderColor='var(--p-warning)'"
           onmouseout="this.style.borderColor='var(--p-border)'">
          <div style="width:52px;height:52px;border-radius:10px;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-warning),#f97316);
                      display:flex;align-items:center;justify-content:center;
                      font-size:20px;font-weight:700;color:#fff">
            <?php if($preview->photo): ?>
              <img src="<?php echo e(asset('storage/'.$preview->photo)); ?>"
                   style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($preview->shop_name,0,1))); ?>

            <?php endif; ?>
          </div>
          <div style="flex:1">
            <div style="font-size:15px;font-weight:600;color:var(--p-text)">
              <?php echo e($preview->shop_name); ?>

              <?php if($preview->isVerified): ?>
                <i class="bi bi-patch-check-fill"
                   style="color:var(--p-info);font-size:13px"></i>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
              <?php echo e($preview->phone_number); ?>

            </div>
            <?php if($preview->rating): ?>
            <div style="font-size:12px;color:var(--p-warning);margin-top:3px">
              ⭐ <?php echo e($preview->rating); ?>

            </div>
            <?php endif; ?>
          </div>
          <i class="bi bi-arrow-up-right-square"
             style="font-size:18px;color:var(--p-accent)"></i>
        </a>

        <?php elseif($marketNews->action === 'to_product' && $preview): ?>
        
        <?php
          $imgs  = is_array($preview->images) ? $preview->images : json_decode($preview->images ?? '[]', true);
          $img   = $imgs[0] ?? null;
          $price = ($preview->discountPrice ?? 0) > 0 ? $preview->discountPrice : $preview->price;
        ?>
        <a href="<?php echo e(route('panel.books.show', $preview->id)); ?>"
           style="display:flex;align-items:center;gap:14px;padding:14px;
                  background:var(--p-elevated);border-radius:12px;
                  border:1px solid var(--p-border);text-decoration:none;
                  transition:border-color .15s"
           onmouseover="this.style.borderColor='var(--p-info)'"
           onmouseout="this.style.borderColor='var(--p-border)'">
          <div style="width:42px;height:58px;border-radius:6px;overflow:hidden;flex-shrink:0;
                      background:var(--p-elevated);display:flex;align-items:center;
                      justify-content:center">
            <?php if($img): ?>
              <img src="<?php echo e($img); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <i class="bi bi-book" style="font-size:18px;color:var(--p-hint)"></i>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:14px;font-weight:600;color:var(--p-text);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?php echo e($preview->name); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint)"><?php echo e($preview->author); ?></div>
            <div style="font-size:13px;font-weight:700;color:var(--p-success);
                        font-family:'DM Mono',monospace;margin-top:4px">
              <?php echo e(number_format($price)); ?> UZS
            </div>
          </div>
          <i class="bi bi-arrow-up-right-square"
             style="font-size:18px;color:var(--p-accent)"></i>
        </a>

        <?php else: ?>
        <div style="padding:24px;text-align:center;color:var(--p-hint)">
          <i class="bi bi-exclamation-circle"
             style="font-size:24px;display:block;margin-bottom:8px"></i>
          ID <strong>#<?php echo e($marketNews->action_id); ?></strong> topilmadi
          (o'chirilgan bo'lishi mumkin)
        </div>
        <?php endif; ?>

      </div>
    </div>

    <?php elseif($marketNews->action === 'news'): ?>
    <div class="p-card fade-up"
         style="background:var(--p-elevated);border-style:dashed">
      <div style="padding:24px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-newspaper"
           style="font-size:28px;display:block;margin-bottom:8px"></i>
        Bu yangilik — bosish orqali hech yerga o'tmaydi
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/market-news/show.blade.php ENDPATH**/ ?>