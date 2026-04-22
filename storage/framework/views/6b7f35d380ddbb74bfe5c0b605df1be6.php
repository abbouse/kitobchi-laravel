<?php $__env->startSection('title', 'Obuna #'.$subscription->id); ?>
<?php $__env->startSection('page-title', 'Mystery Box obuna'); ?>

<?php $__env->startSection('content'); ?>

<?php $addr = is_array($subscription->address) ? $subscription->address : []; ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.mystery-box.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.mystery-box.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Obuna #<?php echo e($subscription->id); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex gap-2">
        <?php if($subscription->status === 'active'): ?>
        <form method="POST" action="<?php echo e(route('admin.mystery-box.pause', $subscription)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p ghost"><i class="bi bi-pause-fill"></i> To'xtatish</button>
        </form>
        <?php elseif($subscription->status === 'paused'): ?>
        <form method="POST" action="<?php echo e(route('admin.mystery-box.resume', $subscription)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p success"><i class="bi bi-play-fill"></i> Davom ettirish</button>
        </form>
        <?php endif; ?>
    
        <?php if(!in_array($subscription->status, ['cancelled','completed'])): ?>
        <form method="POST" action="<?php echo e(route('admin.mystery-box.cancel', $subscription)); ?>"
              onsubmit="return confirm('Obuna bekor qilinsinmi?')">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p danger ghost"><i class="bi bi-x-lg"></i> Bekor qilish</button>
        </form>
        <?php endif; ?>
      </div>
   <?php $__env->endSlot(); ?>
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


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  
  <div class="xl:col-span-4">

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Foydalanuvchi</div></div>
      <div style="padding:14px 18px">
        <?php if($subscription->user): ?>
        <div class="flex items-center gap-3 mb-3">
          <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:16px;font-weight:700;color:#fff">
            <?php if($subscription->user->avatar): ?>
              <img src="<?php echo e(asset('storage/'.$subscription->user->avatar)); ?>"
                   style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($subscription->user->name,0,1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($subscription->user->name); ?> <?php echo e($subscription->user->lastname); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($subscription->user->phone_number); ?>

            </div>
          </div>
        </div>
        <a href="<?php echo e(route('admin.users.show',$subscription->user_id)); ?>"
           class="btn-p ghost" style="width:100%;justify-content:center;font-size:12px">
          <i class="bi bi-person"></i> Profil
        </a>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-geo-alt mr-1" style="color:var(--p-accent)"></i> Yetkazish manzili
        </div>
      </div>
      <div style="padding:14px 18px">
        <?php $__currentLoopData = [
          ['Qabul qiluvchi', $addr['fullName'] ?? '—'],
          ['Telefon',        $addr['phoneNumber'] ?? '—'],
          ['Manzil',         $addr['fullAddress'] ?? '—'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="margin-bottom:12px">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:3px"><?php echo e($k); ?></div>
          <div style="font-size:13px;color:var(--p-text);font-weight:500"><?php echo e($v); ?></div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if(($addr['lat'] ?? null) && ($addr['lon'] ?? null)): ?>
        <a href="https://maps.yandex.uz/?text=<?php echo e($addr['lat']); ?>+<?php echo e($addr['lon']); ?>&z=16"
           target="_blank" class="btn-p ghost sm">
          <i class="bi bi-map"></i> Xaritada ko'rish
        </a>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Tarif</div></div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = [
          ['Tarif',       $subscription->plan?->name_uz ?? '—'],
          ['Muddat',      $subscription->total_months.' oy'],
          ['Har oyda',    $subscription->books_per_month.' ta kitob'],
          ['To\'lov',     number_format($subscription->price_uzs).' UZS'],
          ['Boshlandi',   $subscription->started_at?->format('d.m.Y') ?? '—'],
          ['Tugaydi',     $subscription->ends_at?->format('d.m.Y') ?? '—'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        
        <div style="margin-top:14px">
          <div class="flex justify-between mb-1">
            <span style="font-size:11px;color:var(--p-hint)">Bajarildi</span>
            <span style="font-size:11px;color:var(--p-muted);font-family:'JetBrains Mono',monospace">
              <?php echo e($subscription->delivered_months); ?>/<?php echo e($subscription->total_months); ?>

            </span>
          </div>
          <div style="height:8px;background:var(--p-elevated);border-radius:4px;overflow:hidden">
            <div style="height:100%;background:var(--p-success);border-radius:4px;
                        width:<?php echo e($subscription->progress_pct); ?>%;transition:width .3s">
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  
  <div class="xl:col-span-8">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-box-seam mr-1" style="color:var(--p-accent)"></i>
          Oylik yetkazishlar
        </div>
        <span class="s-pill accent" style="font-size:10px">
          <?php echo e($subscription->deliveries->count()); ?> ta
        </span>
      </div>

      <?php $__empty_1 = true; $__currentLoopData = $subscription->deliveries->sortBy('month_number'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php
        $books = $delivery->book_ids
          ? \App\Models\Books::whereIn('id', $delivery->book_ids)
              ->select('id','name','author','images')->get()
          : collect();
      ?>
      <div style="padding:16px 18px;border-top:1px solid var(--p-border)">
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div style="width:32px;height:32px;border-radius:8px;flex-shrink:0;
                        background:var(--p-elevated);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'JetBrains Mono',monospace;font-size:13px;
                        font-weight:700;color:var(--p-accent)">
              <?php echo e($delivery->month_number); ?>

            </div>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--p-text)">
                <?php echo e($delivery->month_number); ?>-oy
              </div>
              <?php if($delivery->shipped_at): ?>
              <div style="font-size:11px;color:var(--p-hint)">
                Jo'natildi: <?php echo e($delivery->shipped_at->format('d.m.Y')); ?>

              </div>
              <?php elseif($delivery->prepared_at): ?>
              <div style="font-size:11px;color:var(--p-hint)">
                Tayyorlandi: <?php echo e($delivery->prepared_at->format('d.m.Y')); ?>

              </div>
              <?php endif; ?>
            </div>
          </div>
          <span class="s-pill <?php echo e($delivery->status_color); ?>" style="font-size:10px">
            <?php echo e($delivery->status_label); ?>

          </span>
        </div>

        
        <?php if($books->count()): ?>
        <div class="flex flex-wrap gap-2 mb-3">
          <?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $imgs = is_array($book->images) ? $book->images : json_decode($book->images??'[]',true);
            $img  = $imgs[0] ?? null;
          ?>
          <a href="<?php echo e(route('admin.books.show',$book->id)); ?>" target="_blank"
             style="display:flex;align-items:center;gap:8px;padding:6px 10px;
                    background:var(--p-elevated);border-radius:8px;
                    border:1px solid var(--p-border);text-decoration:none;
                    min-width:160px">
            <div style="width:28px;height:38px;border-radius:4px;overflow:hidden;
                        flex-shrink:0;background:var(--p-hover)">
              <?php if($img): ?>
                <img src="<?php echo e($img); ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;
                            justify-content:center">
                  <i class="bi bi-book" style="font-size:12px;color:var(--p-hint)"></i>
                </div>
              <?php endif; ?>
            </div>
            <div style="min-width:0">
              <div style="font-size:11.5px;font-weight:500;color:var(--p-text);
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                          max-width:110px"><?php echo e($book->name); ?></div>
              <div style="font-size:10px;color:var(--p-hint)"><?php echo e($book->author); ?></div>
            </div>
          </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        
        <?php if($delivery->status === 'pending'): ?>
        
        <form method="POST"
              action="<?php echo e(route('admin.mystery-box.prepare', $delivery)); ?>"
              id="prepForm<?php echo e($delivery->id); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <div class="row g-2 items-end">
            <div class="col">
              <label class="p-form-label">
                Kitob IDlari (vergul bilan ajrating)
              </label>
              <input type="text" name="book_ids_raw" class="p-form-control"
                     placeholder="123, 456, 789"
                     oninput="parseBookIds(this,'<?php echo e($delivery->id); ?>')">
              <input type="hidden" name="book_ids" id="bookIds<?php echo e($delivery->id); ?>" value="[]">
            </div>
            <div class="col-auto">
              <input type="text" name="tracking_note" class="p-form-control"
                     placeholder="Izoh (ixtiyoriy)">
            </div>
            <div class="col-auto">
              <button type="submit" class="btn-p primary">
                <i class="bi bi-check-lg"></i> Kitoblarni tasdiqlash
              </button>
            </div>
          </div>
        </form>

        <?php elseif($delivery->status === 'preparing'): ?>
        <div class="flex gap-2">
          <form method="POST" action="<?php echo e(route('admin.mystery-box.ship', $delivery)); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <button class="btn-p primary">
              <i class="bi bi-truck"></i> Jo'natildi
            </button>
          </form>
        </div>

        <?php elseif($delivery->status === 'shipped'): ?>
        <form method="POST" action="<?php echo e(route('admin.mystery-box.deliver', $delivery)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p success">
            <i class="bi bi-check-circle"></i> Yetkazildi
          </button>
        </form>

        <?php elseif($delivery->status === 'delivered'): ?>
        <div style="font-size:12px;color:var(--p-success);display:flex;align-items:center;gap:6px">
          <i class="bi bi-check-circle-fill"></i>
          <?php echo e($delivery->delivered_at?->format('d.m.Y')); ?> da yetkazildi
        </div>
        <?php endif; ?>

        <?php if($delivery->tracking_note): ?>
        <div style="margin-top:8px;font-size:12px;color:var(--p-muted);
                    background:var(--p-elevated);padding:8px 12px;border-radius:6px">
          <i class="bi bi-info-circle mr-1"></i><?php echo e($delivery->tracking_note); ?>

        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div style="padding:36px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>
        Hali yetkazishlar yo'q
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function parseBookIds(input, deliveryId) {
  const ids = input.value.split(',')
    .map(s => parseInt(s.trim()))
    .filter(n => !isNaN(n) && n > 0);
  document.getElementById('bookIds' + deliveryId).value = JSON.stringify(ids);
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/mystery-box/subscription-show.blade.php ENDPATH**/ ?>