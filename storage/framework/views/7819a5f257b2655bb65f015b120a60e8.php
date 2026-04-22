<?php $__env->startSection('title', 'Chat kuzatuv'); ?>
<?php $__env->startSection('page-title', 'Chat kuzatuv'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Chat kuzatuv <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Foydalanuvchilar suhbatlari va AI chat tarixi <?php $__env->endSlot(); ?>
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

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
  <?php $__currentLoopData = [
    [$counts['user'] ?? 0, 'User chat', 'accent', 'bi-people'],
    [$counts['seller'] ?? 0, "Seller chat", 'warning', 'bi-shop-window'],
    [$counts['ai'] ?? 0, 'AI chat', 'info', 'bi-robot'],
    [($counts['user'] ?? 0) + ($counts['seller'] ?? 0) + ($counts['ai'] ?? 0), 'Jami kanal', 'success', 'bi-chat-dots'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $tone, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="p-card flex items-center gap-3 fade-up" style="padding:14px">
      <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-<?php echo e($tone); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($tone); ?>)">
        <i class="bi <?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;color:var(--p-text)"><?php echo e($value); ?></div>
        <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)"><?php echo e($label); ?></div>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Suhbatlar ro'yxati</div>
    <div class="a122-index-header__meta"><?php echo e($conversations->total()); ?> ta suhbat oqimi mavjud</div>
  </div>
</div>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
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
            <div class="flex items-center gap-2">
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
                <a href="<?php echo e(route('admin.users.show',$conv->user_id)); ?>"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  <?php echo e($conv->name); ?> <?php echo e($conv->lastname); ?>

                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  <?php echo e($conv->phone_number); ?>

                </div>
              </div>
            </div>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
            <?php echo e($conv->messages_count ?? '—'); ?>

          </td>
          <td style="font-size:12px;color:var(--p-muted);max-width:200px;
                     white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?php echo e(Str::limit($conv->last_message ?? '—', 40)); ?>

          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            <?php echo e($conv->last_at
              ? \Carbon\Carbon::parse($conv->last_at)->diffForHumans()
              : '—'); ?>

          </td>
          <td>
            <a href="<?php echo e(route('admin.chats.show-ai', $conv->user_id)); ?>"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>

        
        <?php elseif($tab === 'seller'): ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($conv->id); ?>

          </td>
          <td>
            <?php echo $__env->make('a122.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          </td>
          <td>
            <?php if($conv->seller_name): ?>
            <a href="<?php echo e(route('admin.sellers.show', $conv->seller_id_val)); ?>"
               style="font-size:12.5px;font-weight:500;color:var(--p-warning);text-decoration:none">
              <i class="bi bi-shop-window mr-1"></i><?php echo e($conv->seller_name); ?>

            </a>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            <?php echo e($conv->last_message_at
              ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans()
              : '—'); ?>

          </td>
          <td>
            <a href="<?php echo e(route('admin.chats.show', $conv->id)); ?>"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>

        
        <?php else: ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($conv->id); ?>

          </td>
          <td>
            <?php echo $__env->make('a122.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          </td>
          <td>
            <?php if($conv->user2_name): ?>
            <?php echo $__env->make('a122.chats._user-cell', [
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
                     font-family:'JetBrains Mono',monospace">
            <?php echo e($conv->last_message_at
              ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans()
              : '—'); ?>

          </td>
          <td>
            <a href="<?php echo e(route('admin.chats.show', $conv->id)); ?>"
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
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($conversations->firstItem()); ?>–<?php echo e($conversations->lastItem()); ?>

      / <?php echo e($conversations->total()); ?>

    </div>
    <?php echo e($conversations->links('a122.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/chats/index.blade.php ENDPATH**/ ?>