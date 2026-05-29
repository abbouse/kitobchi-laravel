<?php $__env->startSection('title', 'Support murojaat'); ?>
<?php $__env->startSection('page-title', 'Support murojaatlar'); ?>
<?php $__env->startSection('page-eyebrow', 'Support operations'); ?>

<?php $__env->startSection('content'); ?>
<?php
  $supportTabs = [
    'queue' => ['label' => 'Navbatda', 'count' => $counts['queue'] ?? 0],
    'active' => ['label' => 'Aktiv', 'count' => $counts['active'] ?? 0],
    'closed' => ['label' => 'Yopilgan', 'count' => $counts['closed'] ?? 0],
    'rated' => ['label' => 'Baholangan', 'count' => $counts['rated'] ?? 0],
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
  ];

  $toneClass = fn ($sentBy) => match ($sentBy) {
    'admin' => 'primary',
    'operator' => 'warning',
    'system' => 'success',
    default => 'muted',
  };

  $actorLabel = fn ($sentBy) => match ($sentBy) {
    'admin' => 'Admin',
    'operator' => 'Operator',
    'system' => 'Tizim',
    default => 'Foydalanuvchi',
  };
?>

<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Support operations','title' => 'Support murojaatlari','subtitle' => 'Telegram bot orqali kelgan support oqimini navbat, operator va yozishma sifati bo‘yicha boshqaring.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Support operations','title' => 'Support murojaatlari','subtitle' => 'Telegram bot orqali kelgan support oqimini navbat, operator va yozishma sifati bo‘yicha boshqaring.']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input
        type="search"
        name="search"
        value="<?php echo e(request('search')); ?>"
        placeholder="Ism, username, Telegram ID yoki ticket #"
        class="form-control">
    </form>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Jami murojaat','value' => number_format($counts['all'] ?? 0),'meta' => 'Bot orqali kelgan barcha support oqimlari','icon' => 'headset','tone' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Jami murojaat','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['all'] ?? 0)),'meta' => 'Bot orqali kelgan barcha support oqimlari','icon' => 'headset','tone' => 'primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Navbatda','value' => number_format($counts['queue'] ?? 0),'meta' => 'Operator biriktirilishini kutayotgan chatlar','icon' => 'hourglass-split','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Navbatda','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['queue'] ?? 0)),'meta' => 'Operator biriktirilishini kutayotgan chatlar','icon' => 'hourglass-split','tone' => 'warning']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Aktiv chatlar','value' => number_format($counts['active'] ?? 0),'meta' => 'Hozir javob almashinuvida bo‘lgan support oqimlari','icon' => 'lightning-charge','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Aktiv chatlar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['active'] ?? 0)),'meta' => 'Hozir javob almashinuvida bo‘lgan support oqimlari','icon' => 'lightning-charge','tone' => 'info']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Yopilgan / baholangan','value' => number_format($counts['closed'] ?? 0) . ' / ' . number_format($counts['rated'] ?? 0),'meta' => 'Quality signal va tugallangan yordam sessiyalari','icon' => 'check2-circle','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Yopilgan / baholangan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['closed'] ?? 0) . ' / ' . number_format($counts['rated'] ?? 0)),'meta' => 'Quality signal va tugallangan yordam sessiyalari','icon' => 'check2-circle','tone' => 'success']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Filtrlar va navbat segmentlari','meta' => 'Operator, vaqt oralig‘i va ticket statusi bo‘yicha support oqimini toraytiring.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Filtrlar va navbat segmentlari','meta' => 'Operator, vaqt oralig‘i va ticket statusi bo‘yicha support oqimini toraytiring.']); ?>
    <div class="d-flex flex-column gap-3">
      <div class="kc-filter-card">
        <div class="nav nav-pills flex-wrap">
          <?php $__currentLoopData = $supportTabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tabItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a
              href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>"
              class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
              <?php echo e($tabItem['label']); ?>

              <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>">
                <?php echo e(number_format($tabItem['count'])); ?>

              </span>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>

      <form method="GET" class="row g-3">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
        <input type="hidden" name="search" value="<?php echo e(request('search')); ?>">
        <div class="col-12 col-md-4">
          <label class="form-label small text-uppercase fw-semibold text-secondary">Operator</label>
          <select name="operator_id" class="form-select rounded-4 border-0 shadow-sm">
            <option value="">Barchasi</option>
            <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operator): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($operator->telegram_id); ?>" <?php if((string) request('operator_id') === (string) $operator->telegram_id): echo 'selected'; endif; ?>>
                <?php echo e($operator->name ?: ($operator->username ? '@'.$operator->username : $operator->telegram_id)); ?>

              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small text-uppercase fw-semibold text-secondary">Boshlanish sanasi</label>
          <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="form-control rounded-4 border-0 shadow-sm">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small text-uppercase fw-semibold text-secondary">Tugash sanasi</label>
          <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" class="form-control rounded-4 border-0 shadow-sm">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end gap-2">
          <button class="btn-p primary flex-fill">
            <i class="bi bi-funnel"></i>
            <span>Filtrlash</span>
          </button>
          <a href="<?php echo e(route('admin.support.index', ['tab' => $tab])); ?>" class="btn-p ghost">Tozalash</a>
        </div>
      </form>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Support inbox','meta' => $tickets->total() . ' ta support ticket topildi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Support inbox','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tickets->total() . ' ta support ticket topildi.')]); ?>
    <div class="kc-list-shell">
      <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $st = $statuses[$ticket->status] ?? ['label' => $ticket->status, 'class' => 'text-bg-light border'];
          $lastMessage = $ticket->latestMessage;
          $lastTone = $toneClass($lastMessage?->sent_by);
        ?>

        <div class="kc-list-row">
          <div class="d-flex flex-column gap-3 flex-xl-row justify-content-xl-between align-items-xl-start">
            <div class="d-flex align-items-start gap-3 min-w-0 flex-grow-1">
              <?php echo $__env->make('a122.partials.avatar', [
                'name' => $ticket->name ?: 'Noma\'lum',
                'image' => null,
                'class' => 'w-10 h-10 rounded-4 text-sm',
              ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

              <div class="min-w-0 flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <a href="<?php echo e(route('admin.support.show', $ticket)); ?>" class="kc-list-row__title text-decoration-none">
                    <?php echo e($ticket->name ?: 'Noma’lum foydalanuvchi'); ?>

                  </a>
                  <span class="badge rounded-pill text-bg-light border">#<?php echo e($ticket->id); ?></span>
                  <span class="badge rounded-pill <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span>
                  <span class="kc-inline-label"><?php echo e($ticket->messages_count ?? 0); ?> ta xabar</span>
                </div>

                <div class="kc-list-row__meta">
                  <?php if($ticket->username): ?>
                    <span>t.me/<?php echo e($ticket->username); ?></span>
                  <?php endif; ?>
                  <span>Telegram ID: <?php echo e($ticket->user_id ?: '—'); ?></span>
                  <?php if($ticket->created_at): ?>
                    <span><?php echo e($ticket->created_at->format('d.m.Y H:i')); ?></span>
                  <?php endif; ?>
                </div>

                <div class="kc-list-row__snippet">
                  <?php echo e(\Illuminate\Support\Str::limit($lastMessage?->message ?: $ticket->first_msg ?: 'Murojaat matni yo‘q', 220)); ?>

                </div>

                <div class="kc-list-row__chips">
                  <?php if($lastMessage): ?>
                    <span class="kc-inline-label <?php echo e($lastTone); ?>"><?php echo e($actorLabel($lastMessage->sent_by)); ?></span>
                    <span class="kc-inline-label"><?php echo e(strtoupper($lastMessage->message_type)); ?></span>
                    <span class="kc-inline-label"><?php echo e($lastMessage->created_at?->format('d.m H:i')); ?></span>
                  <?php else: ?>
                    <span class="kc-inline-label">Yozishma tarixi hali yo‘q</span>
                  <?php endif; ?>

                  <?php if($ticket->operator): ?>
                    <span class="kc-inline-label primary">
                      Operator: <?php echo e($ticket->operator->name ?? ($ticket->operator->username ? '@'.$ticket->operator->username : $ticket->operator->telegram_id)); ?>

                    </span>
                  <?php elseif($ticket->status === 'queue'): ?>
                    <span class="kc-inline-label warning">Operator tayinlanmagan</span>
                  <?php endif; ?>

                  <?php if($ticket->rating): ?>
                    <span class="kc-inline-label success">Baho: <?php echo e($ticket->rating); ?>/5</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="kc-list-row__actions">
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
        <div class="text-center py-5 text-secondary">
          <i class="bi bi-chat-square-text d-block mb-2" style="font-size:2rem;"></i>
          Murojaatlar topilmadi.
        </div>
      <?php endif; ?>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

  <?php if($tickets->hasPages()): ?>
    <div><?php echo e($tickets->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/support/index.blade.php ENDPATH**/ ?>