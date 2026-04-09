<?php $__env->startSection('title','Adminlar'); ?>
<?php $__env->startSection('page-title','Admin boshqaruvi'); ?>
<?php $__env->startSection('breadcrumb','Panel / Adminlar'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header fade-up d-flex align-items-start justify-content-between">
  <div>
    <h1 class="page-title">Adminlar</h1>
    <p class="page-sub">Panel foydalanuvchilari va ularning ruxsatlari</p>
  </div>
  <a href="<?php echo e(route('panel.admins.create')); ?>" class="btn-p primary">
    <i class="bi bi-plus-lg"></i> Yangi admin
  </a>
</div>

<div class="p-card fade-up">
  <div class="p-card-header">
    <div class="p-card-title">Adminlar ro'yxati</div>
    <div class="p-card-sub"><?php echo e($admins->total()); ?> ta admin</div>
  </div>

  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th><th>Admin</th><th>Rol</th>
          <th>Ruxsatlar</th><th>Oxirgi kirish</th>
          <th>Holat</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td><span style="font-family:'DM Mono',monospace;color:var(--p-accent);font-size:12px">#<?php echo e($admin->id); ?></span></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc);color:#fff">
                <?php echo e(strtoupper(substr($admin->name,0,1))); ?>

              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($admin->name); ?></div>
                <div style="font-size:11px;color:var(--p-hint)"><?php echo e($admin->email); ?></div>
              </div>
            </div>
          </td>
          <td>
            <?php $colors = ['superadmin'=>'danger','admin'=>'accent','moderator'=>'warning']; ?>
            <span class="s-pill <?php echo e($colors[$admin->role] ?? 'muted'); ?>"><?php echo e($admin->role_label); ?></span>
          </td>
          <td>
            <?php if($admin->isSuperAdmin()): ?>
              <span style="font-size:12px;color:var(--p-hint)">Barcha ruxsatlar</span>
            <?php else: ?>
              <div class="d-flex flex-wrap gap-1">
                <?php $__currentLoopData = array_slice($admin->permissions ?? [], 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <span class="s-pill accent" style="font-size:10px;padding:2px 7px"><?php echo e($perm); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if(count($admin->permissions ?? []) > 3): ?>
                  <span style="font-size:11px;color:var(--p-hint)">+<?php echo e(count($admin->permissions)-3); ?> ta</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <?php if($admin->last_login_at): ?>
              <div style="font-size:12px;color:var(--p-text)"><?php echo e($admin->last_login_at->format('d.m.Y H:i')); ?></div>
              <div style="font-size:11px;color:var(--p-hint)"><?php echo e($admin->last_ip); ?></div>
            <?php else: ?>
              <span style="font-size:12px;color:var(--p-hint)">Hali kirмagan</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($admin->is_active): ?>
              <span class="s-pill success">Aktiv</span>
            <?php else: ?>
              <span class="s-pill danger">Bloklangan</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?php echo e(route('panel.admins.edit', $admin)); ?>" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <?php if($admin->id !== auth('panel')->id()): ?>
              <form method="POST" action="<?php echo e(route('panel.admins.toggle', $admin)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p <?php echo e($admin->is_active ? 'danger' : 'success'); ?> sm"
                        title="<?php echo e($admin->is_active ? 'Bloklash' : 'Faollashtirish'); ?>">
                  <i class="bi bi-<?php echo e($admin->is_active ? 'lock' : 'unlock'); ?>"></i>
                </button>
              </form>
              <form method="POST" action="<?php echo e(route('panel.admins.destroy', $admin)); ?>"
                    onsubmit="return confirm('Adminni o\'chirishni tasdiqlaysizmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-shield" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Adminlar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($admins->hasPages()): ?>
  <div class="d-flex align-items-center justify-content-between mt-3"
       style="padding-top:12px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)"><?php echo e($admins->firstItem()); ?>–<?php echo e($admins->lastItem()); ?> / <?php echo e($admins->total()); ?></div>
    <div class="p-pagination">
      <?php if($admins->onFirstPage()): ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      <?php else: ?>
        <a href="<?php echo e($admins->previousPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      <?php endif; ?>
      <?php $__currentLoopData = $admins->getUrlRange(max(1,$admins->currentPage()-2),min($admins->lastPage(),$admins->currentPage()+2)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page=>$url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($url); ?>" class="p-page-btn <?php echo e($page===$admins->currentPage()?'active':''); ?>"><?php echo e($page); ?></a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php if($admins->hasMorePages()): ?>
        <a href="<?php echo e($admins->nextPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      <?php else: ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/admins/index.blade.php ENDPATH**/ ?>