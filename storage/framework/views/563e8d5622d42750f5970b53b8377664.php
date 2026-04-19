<?php $__env->startSection('title','Kitoblar'); ?>
<?php $__env->startSection('page-title','Kitoblar'); ?>
<?php $__env->startSection('breadcrumb','Panel / Kitoblar'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Kitoblar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Barcha kitoblar moderatsiyasi va boshqaruvi <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex gap-2">
        <a href="<?php echo e(route('panel.books.import')); ?>" class="btn-p ghost"><i class="bi bi-upload"></i> Import</a>
        <a href="<?php echo e(route('panel.books.export', request()->all())); ?>" class="btn-p ghost"><i class="bi bi-download"></i> Export</a>
      </div>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>



<div class="tab-pills fade-up">
  <?php $__currentLoopData = ['pending'=>'Kutilmoqda','approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan','all'=>'Barchasi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(route('panel.books.index', array_merge(request()->except('tab','page'), ['tab'=>$key]))); ?>"
     class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
    <?php echo e($label); ?>

    <span class="tab-count">
      <?php echo e($key === 'all' ? array_sum($counts) : ($counts[$key] ?? 0)); ?>

    </span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="GET" action="<?php echo e(route('panel.books.index')); ?>" id="bookFilter">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="filter-bar fade-up">
    <div class="search-box" style="width:200px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Nom, muallif, ID..."/>
    </div>
    <select name="category_id" class="p-form-control" style="width:160px" onchange="bookFilter.submit()">
      <option value="">Kategoriya</option>
      <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($cat->id); ?>" <?php echo e(request('category_id') == $cat->id ? 'selected':''); ?>><?php echo e($cat->name_uz); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select name="seller_id" class="p-form-control" style="width:160px" onchange="bookFilter.submit()">
      <option value="">Sotuvchi</option>
      <?php $__currentLoopData = $sellers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($s->id); ?>" <?php echo e(request('seller_id') == $s->id ? 'selected':''); ?>><?php echo e($s->shop_name); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select name="status" class="p-form-control" style="width:130px" onchange="bookFilter.submit()">
      <option value="">Ko'rinish</option>
      <option value="1" <?php echo e(request('status')==='1'?'selected':''); ?>>Ko'rinadigan</option>
      <option value="0" <?php echo e(request('status')==='0'?'selected':''); ?>>Yashirin</option>
    </select>
    <button type="submit" class="btn-p primary"><i class="bi bi-funnel"></i> Filter</button>
    <?php if(request()->hasAny(['search','category_id','seller_id','status'])): ?>
    <a href="<?php echo e(route('panel.books.index',['tab'=>$tab])); ?>" class="btn-p ghost"><i class="bi bi-x"></i> Tozalash</a>
    <?php endif; ?>
  </div>
</form>


<div class="p-card fade-up">
  <div class="p-card-header">
    <div>
      <div class="p-card-title">Kitoblar ro'yxati</div>
      <div class="p-card-sub"><?php echo e($books->total()); ?> ta natija</div>
    </div>
  </div>
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kitob</th>
          <th>Muallif</th>
          <th>Sotuvchi</th>
          <th>Narx</th>
          <th>Zaxira</th>
          <th>Moderatsiya</th>
          <th>Holat</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td><span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">#<?php echo e($book->id); ?></span></td>
          <td>
            <div class="flex items-center gap-2">
              <?php $img = is_array($book->images) ? ($book->images[0] ?? null) : null; ?>
              <div style="width:38px;height:52px;border-radius:6px;overflow:hidden;background:var(--p-elevated);flex-shrink:0">
                <?php if($img): ?>
                  <img src="<?php echo e(asset('storage/' . $img)); ?>" style="width:100%;height:100%;object-fit:cover" alt="">
                <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-book" style="color:var(--p-hint)"></i>
                  </div>
                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text);max-width:160px" class="text-truncate"><?php echo e($book->name); ?></div>
                <div style="font-size:11px;color:var(--p-hint)"><?php echo e($book->category?->name_uz ?? '—'); ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:13px;color:var(--p-muted)"><?php echo e($book->author); ?></td>
          <td>
            <div style="font-size:12px;color:var(--p-text)"><?php echo e($book->seller?->shop_name ?? '—'); ?></div>
          </td>
          <td>
            <div style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;color:var(--p-text)"><?php echo e(number_format($book->price)); ?></div>
            <?php if($book->discountPrice > 0): ?>
            <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--p-success)">-<?php echo e(number_format($book->discountPrice)); ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($book->count); ?></span>
            <span style="font-size:11px;color:var(--p-hint)"> dona</span>
          </td>
          <td>
            <?php if($book->is_approved == 1): ?>
              <span class="s-pill success">✓ Tasdiqlangan</span>
            <?php elseif($book->is_approved == 2): ?>
              <span class="s-pill danger">✗ Rad etilgan</span>
            <?php else: ?>
              <span class="s-pill warning">⟳ Kutilmoqda</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($book->is_hidden): ?>
              <span class="s-pill danger">Yashirin</span>
            <?php elseif($book->status): ?>
              <span class="s-pill success">Ko'rinadi</span>
            <?php else: ?>
              <span class="s-pill muted">O'chirilgan</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="flex gap-1">
              
              <?php if($book->is_approved != 1): ?>
              <form method="POST" action="<?php echo e(route('panel.books.moderate', $book)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="is_approved" value="1">
                <button class="btn-p success sm" title="Tasdiqlash"><i class="bi bi-check-lg"></i></button>
              </form>
              <?php endif; ?>
              <?php if($book->is_approved != 2): ?>
              <form method="POST" action="<?php echo e(route('panel.books.moderate', $book)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="is_approved" value="2">
                <button class="btn-p danger sm" title="Rad etish"><i class="bi bi-x-lg"></i></button>
              </form>
              <?php endif; ?>
              <a href="<?php echo e(route('panel.books.show', $book)); ?>" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="<?php echo e(route('panel.books.edit', $book)); ?>" class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-book" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Kitoblar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($books->hasPages()): ?>
  <div class="flex items-center justify-between mt-3" style="padding-top:12px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)"><?php echo e($books->firstItem()); ?>–<?php echo e($books->lastItem()); ?> / <?php echo e($books->total()); ?></div>
    <div class="p-pagination">
      <?php if($books->onFirstPage()): ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      <?php else: ?>
        <a href="<?php echo e($books->previousPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      <?php endif; ?>
      <?php $__currentLoopData = $books->getUrlRange(max(1,$books->currentPage()-2), min($books->lastPage(),$books->currentPage()+2)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($url); ?>" class="p-page-btn <?php echo e($page===$books->currentPage()?'active':''); ?>"><?php echo e($page); ?></a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php if($books->hasMorePages()): ?>
        <a href="<?php echo e($books->nextPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      <?php else: ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/books/index.blade.php ENDPATH**/ ?>