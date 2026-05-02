<?php $__env->startSection('title', 'Support murojaat'); ?>
<?php $__env->startSection('page-title', 'Support murojaatlar'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Support murojaatlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Telegram bot orqali kelgan yordam so'rovlari <?php $__env->endSlot(); ?>
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

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5 mb-4">
  <?php $__currentLoopData = [
    [$counts['all'] ?? 0, 'Jami murojaat', 'accent', 'bi-headset'],
    [$counts['queue'] ?? 0, 'Navbatda', 'warning', 'bi-hourglass-split'],
    [$counts['active'] ?? 0, 'Aktiv', 'info', 'bi-lightning-charge'],
    [$counts['closed'] ?? 0, 'Yopilgan', 'muted', 'bi-check2-circle'],
    [$counts['rated'] ?? 0, 'Baholangan', 'success', 'bi-star'],
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
    <div class="a122-index-header__title">Murojaatlar ro'yxati</div>
    <div class="a122-index-header__meta"><?php echo e($tickets->total()); ?> ta support ticket yuklandi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search"></i>
      <input
        type="search"
        name="search"
        value="<?php echo e(request('search')); ?>"
        placeholder="Ism, username, telegram ID yoki ticket #"
      >
    </form>
  </div>
</div>

<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    'queue' => ['Navbatda', $counts['queue'] ?? 0],
    'active' => ['Aktiv', $counts['active'] ?? 0],
    'closed' => ['Yopilgan', $counts['closed'] ?? 0],
    'rated' => ['Baholangan', $counts['rated'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
      <?php echo e($label); ?> <span><?php echo e($count); ?></span>
    </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="a122-section mb-3">
  <div class="a122-section-body">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <div>
        <label class="p-form-label">Operator</label>
        <select name="operator_id" class="p-form-control">
          <option value="">Barchasi</option>
          <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operator): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($operator->telegram_id); ?>" <?php if((string) request('operator_id') === (string) $operator->telegram_id): echo 'selected'; endif; ?>>
              <?php echo e($operator->name ?: ($operator->username ? '@'.$operator->username : $operator->telegram_id)); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <div>
        <label class="p-form-label">Boshlanish sanasi</label>
        <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="p-form-control">
      </div>
      <div>
        <label class="p-form-label">Tugash sanasi</label>
        <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" class="p-form-control">
      </div>
      <div class="flex items-end gap-2">
        <button class="btn-p primary flex-1"><i class="bi bi-funnel"></i> Filtrlash</button>
        <a href="<?php echo e(route('admin.support.index', ['tab' => $tab])); ?>" class="btn-p ghost">Tozalash</a>
      </div>
    </form>
  </div>
</div>

<div class="a122-section">
  <div class="a122-section-head">
    <div>
      <div class="a122-section-head__title">Support inbox</div>
      <div class="a122-section-head__meta">Aktiv yozishmalar, oxirgi javob va tarix shu yerda ko‘rinadi.</div>
    </div>
  </div>
  <div class="a122-section-body">
    <div class="a122-compact-list">
      <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $st = $statuses[$ticket->status] ?? ['label'=>$ticket->status,'class'=>'ob-p'];
          $lastMessage = $ticket->latestMessage;
          $lastActorClass = $lastMessage?->sent_by === 'user'
            ? 'muted'
            : ($lastMessage?->sent_by === 'admin'
              ? 'accent'
              : ($lastMessage?->sent_by === 'operator' ? 'warning' : 'success'));
          $lastActorLabel = $lastMessage?->sent_by === 'user'
            ? 'Foydalanuvchi'
            : ($lastMessage?->sent_by === 'admin'
              ? 'Admin'
              : ($lastMessage?->sent_by === 'operator' ? 'Operator' : 'Tizim'));
        ?>
        <div class="a122-compact-list__item" style="padding:18px 0">
          <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="flex items-start gap-3 min-w-0 flex-1">
              <?php echo $__env->make('a122.partials.avatar', [
                'name' => $ticket->name ?: 'Noma\'lum',
                'image' => null,
                'class' => 'av'
              ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                  <a href="<?php echo e(route('admin.support.show', $ticket)); ?>" class="a122-compact-list__title" style="text-decoration:none">
                    <?php echo e($ticket->name ?: 'Noma\'lum foydalanuvchi'); ?>

                  </a>
                  <span class="s-pill muted" style="font-size:10px;padding:3px 8px">#<?php echo e($ticket->id); ?></span>
                  <span class="o-badge <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span>
                  <span class="s-pill muted" style="font-size:10px;padding:3px 8px"><?php echo e($ticket->messages_count ?? 0); ?> ta xabar</span>
                </div>

                <div class="a122-compact-list__sub" style="margin-bottom:8px">
                  <?php if($ticket->username): ?>
                    <span>t.me/<?php echo e($ticket->username); ?></span>
                    <span>·</span>
                  <?php endif; ?>
                  <span>Telegram ID: <?php echo e($ticket->user_id ?: '—'); ?></span>
                  <?php if($ticket->created_at): ?>
                    <span>·</span>
                    <span><?php echo e($ticket->created_at->format('d.m.Y H:i')); ?></span>
                  <?php endif; ?>
                </div>

                <div style="font-size:13px;line-height:1.65;color:var(--p-text);margin-bottom:10px">
                  <?php echo e(\Illuminate\Support\Str::limit($lastMessage?->message ?: $ticket->first_msg ?: 'Murojaat matni yo‘q', 220)); ?>

                </div>

                <div class="flex flex-wrap items-center gap-2">
                  <?php if($lastMessage): ?>
                    <span class="s-pill <?php echo e($lastActorClass); ?>" style="font-size:10px;padding:3px 8px"><?php echo e($lastActorLabel); ?></span>
                    <span style="font-size:11px;color:var(--p-hint)"><?php echo e(strtoupper($lastMessage->message_type)); ?></span>
                    <span style="font-size:11px;color:var(--p-hint)">· <?php echo e($lastMessage->created_at?->format('d.m H:i')); ?></span>
                  <?php else: ?>
                    <span style="font-size:11px;color:var(--p-hint)">Yozishma tarixi hali yo‘q</span>
                  <?php endif; ?>

                  <?php if($ticket->operator): ?>
                    <span class="s-pill accent" style="font-size:10px;padding:3px 8px">
                      Operator: <?php echo e($ticket->operator->name ?? ($ticket->operator->username ? '@'.$ticket->operator->username : $ticket->operator->telegram_id)); ?>

                    </span>
                  <?php elseif($ticket->status === 'queue'): ?>
                    <span class="s-pill warning" style="font-size:10px;padding:3px 8px">Operator tayinlanmagan</span>
                  <?php endif; ?>

                  <?php if($ticket->rating): ?>
                    <span class="s-pill success" style="font-size:10px;padding:3px 8px">Baho: <?php echo e($ticket->rating); ?>/5</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <a href="<?php echo e(route('admin.support.show', $ticket)); ?>" class="btn-p ghost sm">
                <i class="bi bi-clock-history"></i>
                <span>History</span>
              </a>
              <a href="<?php echo e(route('admin.support.show', $ticket)); ?>" class="btn-p primary sm">
                <i class="bi bi-chat-left-text"></i>
                <span>Chatni ochish</span>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-chat-square-text" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Murojaatlar topilmadi
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php if($tickets->hasPages()): ?>
    <?php echo e($tickets->links('a122.partials.pagination')); ?>

  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/support/index.blade.php ENDPATH**/ ?>