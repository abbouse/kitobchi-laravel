<?php $__env->startSection('title', 'Chat kuzatuv'); ?>
<?php $__env->startSection('page-title', 'Chat kuzatuv'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Chat kuzatuv</h1>
    <p class="page-sub">Foydalanuvchilar suhbatlari va AI chat tarixi</p>
  </div>
</div>


<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    'user'   => ['👤 User–User',    $counts['user']],
    'seller' => ['🏪 User–Do\'kon', $counts['seller']],
    'ai'     => ['🤖 AI Chat',      $counts['ai']],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => [$l, $c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
    <?php echo e($l); ?> <span class="tab-count"><?php echo e($c); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="search-box" style="width:240px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="<?php echo e(request('search')); ?>"
           placeholder="Ism, telefon, do'kon...">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Qidirish
  </button>
  <?php if(request('search')): ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['search'=>null])); ?>" class="btn-p ghost">
    <i class="bi bi-x"></i> Tozalash
  </a>
  <?php endif; ?>
</form>


<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <?php if($tab === 'ai'): ?>
        <tr>
          <th>Foydalanuvchi</th>
          <th>Xabarlar</th>
          <th>So'nggi xabar</th>
          <th>Vaqt</th>
          <th></th>
        </tr>
        <?php elseif($tab === 'seller'): ?>
        <tr>
          <th>#</th>
          <th>Foydalanuvchi</th>
          <th>Do'kon</th>
          <th>So'nggi faollik</th>
          <th></th>
        </tr>
        <?php else: ?>
        <tr>
          <th>#</th>
          <th>Yuboruvchi</th>
          <th>Qabul qiluvchi</th>
          <th>So'nggi faollik</th>
          <th></th>
        </tr>
        <?php endif; ?>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

        
        <?php if($tab === 'ai'): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                          display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                <?php if($conv->avatar): ?>
                  <img src="<?php echo e($conv->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($conv->name??'U',0,1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <a href="<?php echo e(route('panel.users.show',$conv->user_id)); ?>"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  <?php echo e($conv->name); ?> <?php echo e($conv->lastname); ?>

                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'DM Mono',monospace">
                  <?php echo e($conv->phone_number); ?>

                </div>
              </div>
            </div>
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-muted)">
            <?php echo e($conv->messages_count ?? '—'); ?>

          </td>
          <td style="font-size:12px;color:var(--p-muted);max-width:200px;
                     white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?php echo e(Str::limit($conv->last_message ?? '—', 40)); ?>

          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'DM Mono',monospace">
            <?php echo e($conv->last_at
              ? \Carbon\Carbon::parse($conv->last_at)->diffForHumans()
              : '—'); ?>

          </td>
          <td>
            <a href="<?php echo e(route('panel.chats.show-ai', $conv->user_id)); ?>"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>

        
        <?php elseif($tab === 'seller'): ?>
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($conv->id); ?>

          </td>
          <td>
            <?php echo $__env->make('panel.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          </td>
          <td>
            <?php if($conv->seller_name): ?>
            <a href="<?php echo e(route('panel.sellers.show', $conv->seller_id_val)); ?>"
               style="font-size:12.5px;font-weight:500;color:var(--p-warning);text-decoration:none">
              <i class="bi bi-shop-window me-1"></i><?php echo e($conv->seller_name); ?>

            </a>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'DM Mono',monospace">
            <?php echo e($conv->last_message_at
              ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans()
              : '—'); ?>

          </td>
          <td>
            <a href="<?php echo e(route('panel.chats.show', $conv->id)); ?>"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>

        
        <?php else: ?>
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($conv->id); ?>

          </td>
          <td>
            <?php echo $__env->make('panel.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          </td>
          <td>
            <?php if($conv->user2_name): ?>
            <?php echo $__env->make('panel.chats._user-cell', [
              'name'   => $conv->user2_name,
              'lname'  => $conv->user2_lastname,
              'avatar' => $conv->user2_avatar,
              'uid'    => $conv->receiver_id,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
              <span style="color:var(--p-hint);font-size:12px">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'DM Mono',monospace">
            <?php echo e($conv->last_message_at
              ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans()
              : '—'); ?>

          </td>
          <td>
            <a href="<?php echo e(route('panel.chats.show', $conv->id)); ?>"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endif; ?>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="5" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-chat-square" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Suhbatlar yo'q
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($conversations->hasPages()): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($conversations->firstItem()); ?>–<?php echo e($conversations->lastItem()); ?>

      / <?php echo e($conversations->total()); ?>

    </div>
    <?php echo e($conversations->links('panel.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/chats/index.blade.php ENDPATH**/ ?>