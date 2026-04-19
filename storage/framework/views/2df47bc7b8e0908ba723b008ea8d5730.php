<?php $__env->startSection('title', 'Foydalanuvchilar'); ?>
<?php $__env->startSection('page-title', 'Foydalanuvchilar'); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Foydalanuvchilar'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Foydalanuvchilar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Barcha ro'yxatdan o'tgan foydalanuvchilar <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex gap-2">
        <a href="<?php echo e(route('panel.users.import')); ?>" class="btn-p ghost">
          <i class="bi bi-upload"></i> Import
        </a>
        <a href="<?php echo e(route('panel.users.export', request()->all())); ?>" class="btn-p ghost">
          <i class="bi bi-download"></i> Export
        </a>
        <a href="<?php echo e(route('panel.users.create')); ?>" class="btn-p primary">
          <i class="bi bi-plus-lg"></i> Yangi foydalanuvchi
        </a>
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



<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  <div class="fade-up">
    <div class="metric-card" style="border-top-color: var(--p-accent)">
      <div class="metric-icon" style="background:var(--p-accent-d);color:var(--p-accent)"><i class="bi bi-people-fill"></i></div>
      <div class="metric-label">Jami</div>
      <div class="metric-value"><?php echo e(number_format($stats['total'])); ?></div>
      <span class="s-pill accent"><i class="bi bi-plus"></i><?php echo e($stats['today']); ?> bugun</span>
    </div>
  </div>
  <div class="fade-up">
    <div class="metric-card" style="border-top-color: var(--p-warning)">
      <div class="metric-icon" style="background:var(--p-warning-d);color:var(--p-warning)"><i class="bi bi-star-fill"></i></div>
      <div class="metric-label">Premium</div>
      <div class="metric-value"><?php echo e(number_format($stats['premium'])); ?></div>
      <span class="s-pill warning"><?php echo e($stats['total'] > 0 ? round($stats['premium']/$stats['total']*100) : 0); ?>% ulushi</span>
    </div>
  </div>
  <div class="fade-up">
    <div class="metric-card" style="border-top-color: var(--p-success)">
      <div class="metric-icon" style="background:var(--p-success-d);color:var(--p-success)"><i class="bi bi-circle-fill"></i></div>
      <div class="metric-label">Online</div>
      <div class="metric-value"><?php echo e(number_format($stats['online'])); ?></div>
      <span class="s-pill success">Hozir aktiv</span>
    </div>
  </div>
  <div class="fade-up">
    <div class="metric-card" style="border-top-color: var(--p-info)">
      <div class="metric-icon" style="background:var(--p-info-d);color:var(--p-info)"><i class="bi bi-person-plus-fill"></i></div>
      <div class="metric-label">Bugun yangi</div>
      <div class="metric-value"><?php echo e(number_format($stats['today'])); ?></div>
      <span class="s-pill info">Bugun qo'shildi</span>
    </div>
  </div>
</div>


<form method="GET" action="<?php echo e(route('panel.users.index')); ?>" id="filterForm">
<div class="filter-bar fade-up">
  <div class="search-box" style="width:220px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism, telefon, ID..."/>
  </div>

  <select name="is_premium" class="p-form-control" style="width:140px" onchange="document.getElementById('filterForm').submit()">
    <option value="">Premium</option>
    <option value="1" <?php echo e(request('is_premium') === '1' ? 'selected' : ''); ?>>Premium ✓</option>
    <option value="0" <?php echo e(request('is_premium') === '0' ? 'selected' : ''); ?>>Oddiy</option>
  </select>

  <select name="isVerified" class="p-form-control" style="width:140px" onchange="document.getElementById('filterForm').submit()">
    <option value="">Tasdiqlash</option>
    <option value="1" <?php echo e(request('isVerified') === '1' ? 'selected' : ''); ?>>Tasdiqlangan</option>
    <option value="0" <?php echo e(request('isVerified') === '0' ? 'selected' : ''); ?>>Tasdiqlanmagan</option>
  </select>

  <select name="sort_by" class="p-form-control" style="width:150px" onchange="document.getElementById('filterForm').submit()">
    <option value="id"           <?php echo e(request('sort_by','id') === 'id'           ? 'selected' : ''); ?>>ID bo'yicha</option>
    <option value="created_at"   <?php echo e(request('sort_by') === 'created_at'         ? 'selected' : ''); ?>>Sana bo'yicha</option>
    <option value="real_balance" <?php echo e(request('sort_by') === 'real_balance'       ? 'selected' : ''); ?>>Balans bo'yicha</option>
  </select>

  <select name="sort_dir" class="p-form-control" style="width:110px" onchange="document.getElementById('filterForm').submit()">
    <option value="desc" <?php echo e(request('sort_dir','desc') === 'desc' ? 'selected' : ''); ?>>Kamayib</option>
    <option value="asc"  <?php echo e(request('sort_dir') === 'asc'         ? 'selected' : ''); ?>>O'sib</option>
  </select>

  <button type="submit" class="btn-p primary"><i class="bi bi-funnel"></i> Filter</button>

  <?php if(request()->hasAny(['search','is_premium','isVerified','sort_by'])): ?>
  <a href="<?php echo e(route('panel.users.index')); ?>" class="btn-p ghost"><i class="bi bi-x"></i> Tozalash</a>
  <?php endif; ?>
</div>
</form>


<div class="p-card fade-up">
  <div class="p-card-header">
    <div>
      <div class="p-card-title">Foydalanuvchilar ro'yxati</div>
      <div class="p-card-sub">Jami <?php echo e($users->total()); ?> ta natija</div>
    </div>
  </div>

  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Foydalanuvchi</th>
          <th>Telefon</th>
          <th>Balans</th>
          <th>Premium</th>
          <th>Ishonchlilik</th>
          <th>Qo'shilgan</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td>
            <span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">#<?php echo e($user->id); ?></span>
          </td>
          <td>
            <div class="flex items-center gap-2">
              <div class="av av-blue">
                <?php if($user->avatar): ?>
                  <img src="<?php echo e(Storage::url($user->avatar)); ?>" alt="">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($user->name ?? 'U', 0, 1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  <?php echo e($user->name); ?> <?php echo e($user->lastname); ?>

                </div>
                <div style="font-size:11px;color:var(--p-hint)"><?php echo e($user->verifyCode ?: 'Avtorizatsiyadan o\'tgan'); ?></div>
              </div>
            </div>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?php if($user->phone_number): ?>
        +<?php echo e(preg_replace('/(\d{3})(\d{2})(\d{3})(\d{2})(\d{2})/', '$1 ($2) $3 $4 $5', $user->phone_number)); ?>

    <?php else: ?>
        —
    <?php endif; ?></td>
          <td>
            <span style="font-family:'JetBrains Mono',monospace;font-weight:500;color:var(--p-text)">
              <?php echo e(number_format($user->real_balance ?? 0)); ?>

            </span>
            <span style="font-size:11px;color:var(--p-hint)"> UZS</span>
          </td>
          <td>
            <?php if($user->is_premium): ?>
              <span class="s-pill warning"><i class="bi bi-star-fill" style="font-size:9px"></i> Premium</span>
            <?php else: ?>
              <span class="s-pill muted">Oddiy</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($user->isVerified): ?>
              <span class="s-pill success">Ishonchli</span>
            <?php else: ?>
              <span class="s-pill danger">Tekshirilmagan</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
            <?php echo e($user->created_at?->format('d.m.Y')); ?>

          </td>
          <td>
            <div class="flex items-center gap-1">
              <a href="<?php echo e(route('panel.users.show', $user)); ?>" class="btn-p ghost sm" title="Ko'rish">
                <i class="bi bi-eye"></i>
              </a>
              <a href="<?php echo e(route('panel.users.edit', $user)); ?>" class="btn-p ghost sm" title="Tahrirlash">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST" action="<?php echo e(route('panel.users.destroy', $user)); ?>"
                    onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn-p danger sm" title="O'chirish">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Foydalanuvchilar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  
  <?php if($users->hasPages()): ?>
  <div class="flex items-center justify-between mt-3" style="padding-top:12px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($users->firstItem()); ?>–<?php echo e($users->lastItem()); ?> / <?php echo e($users->total()); ?> ta natija
    </div>
    <div class="p-pagination">
      <?php if($users->onFirstPage()): ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      <?php else: ?>
        <a href="<?php echo e($users->previousPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      <?php endif; ?>

      <?php $__currentLoopData = $users->getUrlRange(max(1,$users->currentPage()-2), min($users->lastPage(),$users->currentPage()+2)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($url); ?>" class="p-page-btn <?php echo e($page === $users->currentPage() ? 'active' : ''); ?>"><?php echo e($page); ?></a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      <?php if($users->hasMorePages()): ?>
        <a href="<?php echo e($users->nextPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      <?php else: ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/users/index.blade.php ENDPATH**/ ?>