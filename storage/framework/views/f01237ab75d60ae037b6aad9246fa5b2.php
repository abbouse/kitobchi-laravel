<?php $__env->startSection('title', 'Suhbat #'.$conversation->id); ?>
<?php $__env->startSection('page-title', 'Suhbat #'.$conversation->id); ?>

<?php $__env->startSection('content'); ?>

<?php
  $isShop = !empty($conversation->shop_id);
  // Ishtirokchi 1: user_id → u1
  $p1Name  = trim(($conversation->user1_name??'') . ' ' . ($conversation->user1_lastname??''));
  $p1Phone = $conversation->user1_phone ?? '';
  $p1Av    = $conversation->user1_avatar ?? null;
  $p1Id    = $conversation->user_id;
  // Ishtirokchi 2: seller yoki receiver
  $p2Name  = $isShop
    ? ($conversation->seller_name ?? 'Do\'kon')
    : trim(($conversation->user2_name??'') . ' ' . ($conversation->user2_lastname??''));
  $p2Phone = $conversation->user2_phone ?? '';
  $p2Av    = $conversation->user2_avatar ?? null;
  $p2Id    = $isShop ? null : $conversation->receiver_id;
  $p2Route = $isShop
    ? route('admin.sellers.show', $conversation->seller_id_val ?? 0)
    : ($p2Id ? route('admin.users.show', $p2Id) : null);
?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.chats.index', ['tab' => $isShop ? 'seller' : 'user'])).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.chats.index', ['tab' => $isShop ? 'seller' : 'user'])).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Suhbat #<?php echo e($conversation->id); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e($p1Name); ?> ↔
      <?php if($isShop): ?>
        <i class="bi bi-shop-window mr-1"></i><?php echo e($p2Name); ?>

      <?php else: ?>
        <?php echo e($p2Name); ?>

      <?php endif; ?> <?php $__env->endSlot(); ?>
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

<section class="a122-section mb-4">
  <div class="a122-section-body">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi-soft">
        <div class="metric-label">Suhbat turi</div>
        <div class="metric-value text-xl"><?php echo e($isShop ? 'User ↔ Shop' : 'Direct'); ?></div>
        <div class="metric-meta">Kanal tipi</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Jami xabarlar</div>
        <div class="metric-value text-xl"><?php echo e(number_format($messages->total())); ?></div>
        <div class="metric-meta">Paginated oqim</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Shikoyatlar</div>
        <div class="metric-value text-xl"><?php echo e(number_format(count($reportedIds))); ?></div>
        <div class="metric-meta">Flag qilingan xabarlar</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Yangilangan</div>
        <div class="metric-value text-xl"><?php echo e(optional($conversation->updated_at)->format('d.m') ?: '—'); ?></div>
        <div class="metric-meta"><?php echo e(optional($conversation->updated_at)->format('H:i') ?: 'Vaqt yo‘q'); ?></div>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  
  <div class="xl:col-span-3">
    <div class="a122-section mb-3 fade-up">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Ishtirokchilar</div>
          <div class="a122-section-head__meta">Profilga o‘tish, aloqa va chat tomonlari.</div>
        </div>
      </div>
      <div class="a122-section-body">

        
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;
                    border-bottom:1px solid var(--p-border)">
          <div style="width:36px;height:36px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:13px;font-weight:700;color:#fff">
            <?php if($p1Av): ?>
              <img src="<?php echo e($p1Av); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($p1Name ?? 'U', 0, 1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <a href="<?php echo e(route('admin.users.show',$p1Id)); ?>"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none">
              <?php echo e($p1Name); ?>

            </a>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($p1Phone); ?>

            </div>
          </div>
        </div>

        
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0">
          <div style="width:36px;height:36px;
                      border-radius:<?php echo e($isShop ? '8px' : '50%'); ?>;
                      overflow:hidden;flex-shrink:0;
                      background:<?php echo e($isShop
                        ? 'linear-gradient(135deg,var(--p-warning),#f97316)'
                        : 'linear-gradient(135deg,var(--p-info),#0ea5e9)'); ?>;
                      display:flex;align-items:center;justify-content:center;
                      font-size:13px;font-weight:700;color:#fff">
            <?php if($p2Av): ?>
              <img src="<?php echo e($p2Av); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($p2Name ?? 'U', 0, 1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <?php if($p2Route): ?>
            <a href="<?php echo e($p2Route); ?>"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none">
              <?php if($isShop): ?><i class="bi bi-shop-window mr-1" style="color:var(--p-warning)"></i><?php endif; ?>
              <?php echo e($p2Name); ?>

            </a>
            <?php else: ?>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($p2Name); ?></div>
            <?php endif; ?>
            <?php if($p2Phone): ?>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($p2Phone); ?>

            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    
    <div class="a122-section fade-up">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Statistika</div>
          <div class="a122-section-head__meta">Suhbat oqimining tezkor nazorat ko‘rsatkichlari.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <?php $__currentLoopData = [
          ['Jami xabarlar',     $messages->total()],
          ['Shikoyatli xabar',  count($reportedIds).' ta'],
          ['So‘nggi yangilanish', optional($conversation->updated_at)->format('d.m.Y H:i') ?: '—'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;
                       color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  </div>

  
  <div class="xl:col-span-9">
    <div class="a122-section fade-up">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Xabarlar</div>
          <div class="a122-section-head__meta">Suhbat oqimi, media va flag qilingan bubble’lar bilan.</div>
        </div>
        <?php if(count($reportedIds) > 0): ?>
        <span class="s-pill danger" style="font-size:10px">
          <i class="bi bi-flag-fill mr-1"></i><?php echo e(count($reportedIds)); ?> shikoyatli
        </span>
        <?php endif; ?>
      </div>
      <div style="padding:0 18px 18px;max-height:65vh;overflow-y:auto" id="msg-scroll">

        <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          // sender_id == user_id (conversation boshlagan) bo'lsa chap, aks holda o'ng
          $isLeft    = $msg->sender_id == $conversation->user_id;
          $isReport  = in_array($msg->id, $reportedIds);
          $senderAv  = $isLeft ? $p1Av  : $p2Av;
          $senderInitial = strtoupper(substr($isLeft ? $p1Name : $p2Name, 0, 1));
        ?>
        <div style="display:flex;gap:10px;margin-bottom:12px;
                    <?php echo e($isLeft ? '' : 'flex-direction:row-reverse'); ?>">

          
          <div style="width:28px;height:28px;border-radius:<?php echo e($isLeft?'50%':($isShop?'8px':'50%')); ?>;
                      flex-shrink:0;margin-top:2px;overflow:hidden;
                      background:<?php echo e($isLeft
                        ? 'linear-gradient(135deg,var(--p-accent),#7c5cfc)'
                        : ($isShop
                          ? 'linear-gradient(135deg,var(--p-warning),#f97316)'
                          : 'linear-gradient(135deg,var(--p-info),#0ea5e9)')); ?>;
                      display:flex;align-items:center;justify-content:center;
                      font-size:11px;font-weight:700;color:#fff">
            <?php if($senderAv): ?>
              <img src="<?php echo e($senderAv); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e($senderInitial); ?>

            <?php endif; ?>
          </div>

          
          <div style="max-width:70%;
                      <?php echo e($isLeft ? '' : 'align-items:flex-end;display:flex;flex-direction:column'); ?>">
            <div style="background:<?php echo e($isReport
              ? 'rgba(255,92,106,.1)'
              : ($isLeft ? 'var(--p-elevated)' : 'rgba(79,124,255,.1)')); ?>;
                        border:1px solid <?php echo e($isReport ? 'rgba(255,92,106,.3)' : 'var(--p-border)'); ?>;
                        border-radius:<?php echo e($isLeft ? '4px 12px 12px 12px' : '12px 4px 12px 12px'); ?>;
                        padding:10px 13px">

              <?php if($isReport): ?>
              <div style="font-size:10px;color:var(--p-danger);margin-bottom:5px;
                          display:flex;align-items:center;gap:4px">
                <i class="bi bi-flag-fill"></i> Shikoyat qilingan
              </div>
              <?php endif; ?>

              <?php if($msg->message ?? null): ?>
              <div style="font-size:13px;color:var(--p-text);line-height:1.6;
                          word-break:break-word">
                <?php echo e($msg->message); ?>

              </div>
              <?php endif; ?>

              <?php if($msg->image ?? null): ?>
              <img src="<?php echo e($msg->image); ?>"
                   style="max-width:200px;border-radius:6px;margin-top:6px;display:block">
              <?php endif; ?>
            </div>
            <div style="font-size:10px;color:var(--p-hint);margin-top:3px;
                        font-family:'JetBrains Mono',monospace;
                        <?php echo e($isLeft ? '' : 'text-align:right'); ?>">
              <?php echo e(\Carbon\Carbon::parse($msg->created_at)->format('d.m H:i')); ?>

              <?php if($msg->is_read ?? false): ?>
                <i class="bi bi-check2-all" style="color:var(--p-info)"></i>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:30px;color:var(--p-hint)">
          Xabarlar yo'q
        </div>
        <?php endif; ?>
      </div>

      <?php if($messages->hasPages()): ?>
      <div style="border-top:1px solid var(--p-border);padding:10px 18px;
                  display:flex;justify-content:center">
        <?php echo e($messages->links('a122.partials.pagination')); ?>

      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Auto scroll to bottom on load
const el = document.getElementById('msg-scroll');
if (el) el.scrollTop = el.scrollHeight;
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/chats/show.blade.php ENDPATH**/ ?>