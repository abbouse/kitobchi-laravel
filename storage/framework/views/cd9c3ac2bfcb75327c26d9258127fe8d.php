<?php $__env->startSection('title', $book->name); ?>
<?php $__env->startSection('page-title', $book->name); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Kitoblar / Ko\'rish'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header fade-up d-flex align-items-start justify-content-between">
  <div class="d-flex align-items-center gap-3">
    <a href="<?php echo e(route('panel.books.index')); ?>" class="btn-p ghost icon"><i class="bi bi-arrow-left"></i></a>
    <div>
      <h1 class="page-title"><?php echo e($book->name); ?></h1>
      <p class="page-sub"><?php echo e($book->author); ?> · ID: #<?php echo e($book->id); ?></p>
    </div>
  </div>
  <div class="d-flex gap-2">
    <?php if($book->is_approved != 1): ?>
    <form method="POST" action="<?php echo e(route('panel.books.moderate', $book)); ?>">
      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
      <input type="hidden" name="is_approved" value="1">
      <button class="btn-p success"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
    </form>
    <?php endif; ?>
    <?php if($book->is_approved != 2): ?>
    <form method="POST" action="<?php echo e(route('panel.books.moderate', $book)); ?>">
      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
      <input type="hidden" name="is_approved" value="2">
      <button class="btn-p danger"><i class="bi bi-x-lg"></i> Rad etish</button>
    </form>
    <?php endif; ?>
    <a href="<?php echo e(route('panel.books.edit', $book)); ?>" class="btn-p primary"><i class="bi bi-pencil"></i> Tahrirlash</a>
  </div>
</div>

<div class="row g-3">

  
  <div class="col-xl-4 fade-up d1">
    <div class="p-card mb-3">
      
      <?php $imgs = is_array($book->images) ? $book->images : []; ?>
      <?php if(count($imgs)): ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:6px;margin-bottom:14px">
        <?php $__currentLoopData = $imgs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="border-radius:8px;overflow:hidden;aspect-ratio:2/3;background:var(--p-elevated)">
          <img src="<?php echo e(asset('storage/' . $img)); ?>" style="width:100%;height:100%;object-fit:cover" alt="Photo">
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
      <?php endif; ?>

      
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if($book->is_approved == 1): ?>
          <span class="s-pill success">✓ Tasdiqlangan</span>
        <?php elseif($book->is_approved == 2): ?>
          <span class="s-pill danger">✗ Rad etilgan</span>
        <?php else: ?>
          <span class="s-pill warning">⟳ Kutilmoqda</span>
        <?php endif; ?>

        <?php if($book->status): ?>
          <span class="s-pill accent">Ko'rinadi</span>
        <?php else: ?>
          <span class="s-pill muted">Ko'rinmaydi</span>
        <?php endif; ?>

        <?php if($book->is_hidden): ?>
          <span class="s-pill danger">Yashirin</span>
        <?php endif; ?>
      </div>

      
      <?php
        $info = [
          ['label'=>'Kategoriya','value'=>$book->category?->name_uz ?? '—'],
          ['label'=>'Sotuvchi','value'=>$book->seller?->shop_name ?? '—'],
          ['label'=>'Til','value'=>$book->lang ?? '—'],
          ['label'=>'Yozuv','value'=>$book->langType ?? '—'],
          ['label'=>'Muqova','value'=>$book->coverType ?? '—'],
          ['label'=>'Yili','value'=>$book->year ?? '—'],
          ['label'=>'Sahifalar','value'=>($book->pages ?? '—').' bet'],
          ['label'=>'Zaxira','value'=>($book->count ?? 0).' dona'],
        ];
      ?>
      <?php $__currentLoopData = $info; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="d-flex justify-content-between align-items-center mb-2"
           style="padding:7px 0;border-bottom:1px solid var(--p-border)">
        <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
        <span style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($row['value']); ?></span>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  
  <div class="col-xl-8">

    
    <div class="row g-3 mb-3">
      <div class="col-md-4 fade-up d1">
        <div class="p-card text-center">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Asosiy narx</div>
          <div style="font-size:20px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-text)">
            <?php echo e(number_format($book->price)); ?>

          </div>
          <div style="font-size:11px;color:var(--p-hint)">UZS</div>
        </div>
      </div>
      <div class="col-md-4 fade-up d2">
        <div class="p-card text-center">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Chegirma narxi</div>
          <div style="font-size:20px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-success)">
            <?php echo e(number_format($book->discountPrice ?? 0)); ?>

          </div>
          <div style="font-size:11px;color:var(--p-hint)">UZS</div>
        </div>
      </div>
      <div class="col-md-4 fade-up d3">
        <div class="p-card text-center">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Chegirma %</div>
          <div style="font-size:20px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-warning)">
            <?php echo e($book->discount_percent ?? 0); ?>%
          </div>
        </div>
      </div>
    </div>

    
    <?php if($book->description): ?>
    <div class="p-card mb-3 fade-up d2">
      <div class="p-card-title mb-2">Kitob haqida</div>
      <div style="font-size:13px;color:var(--p-muted);line-height:1.7"><?php echo e($book->description); ?></div>
    </div>
    <?php endif; ?>

    
    <?php if($book->tags->count()): ?>
    <div class="p-card mb-3 fade-up d3">
      <div class="p-card-title mb-2">Teglar</div>
      <div class="d-flex flex-wrap gap-2">
        <?php $__currentLoopData = $book->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <span class="s-pill accent" style="font-size:12px"><?php echo e($tag->tag_name_uz ?? $tag->name); ?></span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

    
    <div class="p-card fade-up d3">
      <div class="p-card-title mb-3">Savdo statistikasi</div>
      <div class="row g-3">
        <?php
          $stats = [
            ['label'=>'Jami sotildi','value'=>number_format($book->totalSales ?? 0).' ta','color'=>'var(--p-accent)'],
            ['label'=>'Jami daromad','value'=>number_format($book->totalRevenue ?? 0).' UZS','color'=>'var(--p-success)'],
            ['label'=>'Jami mijozlar','value'=>number_format($book->totalClients ?? 0).' ta','color'=>'var(--p-info)'],
            ['label'=>'Bu hafta','value'=>number_format($book->totalSalesWeek ?? 0).' ta','color'=>'var(--p-warning)'],
          ];
        ?>
        <?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-md-3">
          <div style="text-align:center;padding:12px;background:var(--p-elevated);border-radius:8px">
            <div style="font-size:15px;font-weight:700;font-family:'DM Mono',monospace;color:<?php echo e($s['color']); ?>"><?php echo e($s['value']); ?></div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:3px"><?php echo e($s['label']); ?></div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/books/show.blade.php ENDPATH**/ ?>