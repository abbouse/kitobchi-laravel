<?php $__env->startSection('title', 'Sertifikat '.$giftCertificate->code); ?>
<?php $__env->startSection('page-title', 'Gift Sertifikat'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="<?php echo e(route('panel.gift-certificates.index')); ?>" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title" style="font-family:'DM Mono',monospace;letter-spacing:.04em">
        <?php echo e($giftCertificate->code); ?>

      </h1>
      <p class="page-sub">
        Yaratildi: <?php echo e($giftCertificate->created_at?->format('d.m.Y H:i')); ?>

        <?php if($giftCertificate->expires_at && $giftCertificate->status === 'sent'): ?>
          · <span style="color:<?php echo e($giftCertificate->is_expired ? 'var(--p-danger)' : 'var(--p-warning)'); ?>">
            Muddati: <?php echo e($giftCertificate->expires_at->format('d.m.Y')); ?>

            (<?php echo e($giftCertificate->expires_at->diffForHumans()); ?>)
          </span>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <div class="d-flex gap-2">
    
    <?php if(!in_array($giftCertificate->status, ['used','cancelled'])): ?>
    <div class="dropdown">
      <button class="btn-p ghost" data-bs-toggle="dropdown">
        <i class="bi bi-chevron-down"></i> Status
      </button>
      <ul class="dropdown-menu dropdown-menu-end"
          style="background:var(--p-surface);border:1px solid var(--p-border);
                 border-radius:10px;min-width:180px;padding:6px">
        <?php $__currentLoopData = [
          'pending_payment' => 'To\'lov kutilmoqda',
          'paid'            => 'To\'landi',
          'sent'            => 'Yuborildi',
          'used'            => 'Ishlatildi',
          'cancelled'       => 'Bekor qilindi',
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $lbl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li>
          <form method="POST"
                action="<?php echo e(route('panel.gift-certificates.status', $giftCertificate)); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <input type="hidden" name="status" value="<?php echo e($val); ?>">
            <button type="submit" class="dropdown-item"
                    style="font-size:13px;padding:8px 12px;border-radius:6px;
                           background:<?php echo e($val===$giftCertificate->status?'var(--p-elevated)':'transparent'); ?>;
                           color:<?php echo e($val==='cancelled'?'var(--p-danger)':($val===$giftCertificate->status?'var(--p-accent)':'var(--p-text)')); ?>">
              <?php echo e($val===$giftCertificate->status ? '● ' : '○ '); ?><?php echo e($lbl); ?>

            </button>
          </form>
        </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>

    <form method="POST"
          action="<?php echo e(route('panel.gift-certificates.cancel', $giftCertificate)); ?>"
          onsubmit="return confirm('Bekor qilinsinmi?')">
      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
      <button class="btn-p danger ghost">
        <i class="bi bi-x-lg"></i> Bekor qilish
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">

  
  <div class="col-xl-4">

    
    <div class="p-card mb-3 fade-up" style="overflow:hidden">
      <div style="padding:24px 22px;background:linear-gradient(135deg,
                  var(--p-accent-d) 0%,var(--p-elevated) 100%);
                  border-bottom:1px solid var(--p-border)">
        <div style="display:flex;align-items:center;justify-content:space-between;
                    margin-bottom:20px">
          <div style="font-size:12px;font-weight:600;color:var(--p-muted);
                      text-transform:uppercase;letter-spacing:.1em">
            Gift Certificate
          </div>
          <i class="bi bi-gift" style="font-size:22px;color:var(--p-accent)"></i>
        </div>
        <div style="font-size:26px;font-weight:700;font-family:'DM Mono',monospace;
                    color:var(--p-success);margin-bottom:4px">
          <?php echo e(number_format($giftCertificate->nominal_uzs)); ?>

          <span style="font-size:14px;font-weight:400">UZS</span>
        </div>
        <div style="font-size:11px;color:var(--p-muted)">Balans sifatida qo'shiladi</div>
      </div>
      <div style="padding:14px 22px">
        <div style="font-family:'DM Mono',monospace;font-size:15px;font-weight:700;
                    color:var(--p-text);letter-spacing:.1em;margin-bottom:8px">
          <?php echo e($giftCertificate->code); ?>

        </div>
        <span class="s-pill <?php echo e($giftCertificate->status_color); ?>" style="font-size:11px">
          <?php echo e($giftCertificate->status_label); ?>

        </span>
      </div>
    </div>

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Tafsilotlar</div></div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = [
          ['Miqdor',       number_format($giftCertificate->nominal_uzs).' UZS'],
          ['Yaratildi',    $giftCertificate->created_at?->format('d.m.Y H:i')],
          ['To\'landi',    $giftCertificate->paid_at?->format('d.m.Y H:i') ?? '—'],
          ['Yuborildi',    $giftCertificate->sent_at?->format('d.m.Y H:i') ?? '—'],
          ['Muddati',      $giftCertificate->expires_at?->format('d.m.Y') ?? 'Muddatsiz'],
          ['Ishlatildi',   $giftCertificate->used_at?->format('d.m.Y H:i') ?? '—'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text);
                       font-family:'DM Mono',monospace"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($giftCertificate->message): ?>
        <div style="margin-top:12px;padding:10px;background:var(--p-elevated);
                    border-radius:7px;border-left:3px solid var(--p-accent)">
          <div style="font-size:10px;color:var(--p-hint);margin-bottom:4px">
            Shaxsiy xabar:
          </div>
          <div style="font-size:13px;color:var(--p-muted);font-style:italic">
            "<?php echo e($giftCertificate->message); ?>"
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  
  <div class="col-xl-8">
    <div class="row g-3">

      
      <div class="col-md-6">
        <div class="p-card h-100 fade-up">
          <div class="p-card-header">
            <div class="p-card-title">
              <i class="bi bi-person-fill me-1" style="color:var(--p-info)"></i>
              Sotib olgan
            </div>
            <span class="s-pill info" style="font-size:10px">Buyer</span>
          </div>
          <div style="padding:16px 18px">
            <?php if($giftCertificate->buyer): ?>
            <?php $buyer = $giftCertificate->buyer; ?>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;
                          flex-shrink:0;background:linear-gradient(135deg,var(--p-info),#0ea5e9);
                          display:flex;align-items:center;justify-content:center;
                          font-size:16px;font-weight:700;color:#fff">
                <?php if($buyer->avatar): ?>
                  <img src="<?php echo e(asset('storage/'.$buyer->avatar)); ?>"
                       style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($buyer->name,0,1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:14px;font-weight:600;color:var(--p-text)">
                  <?php echo e($buyer->name); ?> <?php echo e($buyer->lastname); ?>

                </div>
                <div style="font-size:12px;color:var(--p-hint);font-family:'DM Mono',monospace">
                  <?php echo e($buyer->phone_number); ?>

                </div>
              </div>
            </div>
            <a href="<?php echo e(route('panel.users.show',$buyer->id)); ?>"
               class="btn-p ghost sm" style="width:100%;justify-content:center">
              <i class="bi bi-person-lines-fill"></i> Profil
            </a>
            <?php else: ?>
            <div style="text-align:center;padding:20px;color:var(--p-hint)">
              <i class="bi bi-person-dash" style="font-size:24px;display:block;margin-bottom:6px"></i>
              Foydalanuvchi topilmadi
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      
      <div class="col-md-6">
        <div class="p-card h-100 fade-up">
          <div class="p-card-header">
            <div class="p-card-title">
              <i class="bi bi-gift-fill me-1" style="color:var(--p-success)"></i>
              Qabul qiluvchi
            </div>
            <span class="s-pill <?php echo e($giftCertificate->recipient ? 'success' : 'muted'); ?>"
                  style="font-size:10px">
              <?php echo e($giftCertificate->recipient ? 'Ro\'yxatdan o\'tgan' : 'Kutilmoqda'); ?>

            </span>
          </div>
          <div style="padding:16px 18px">
            <?php if($giftCertificate->recipient): ?>
            <?php $rec = $giftCertificate->recipient; ?>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;
                          flex-shrink:0;background:linear-gradient(135deg,var(--p-success),#059669);
                          display:flex;align-items:center;justify-content:center;
                          font-size:16px;font-weight:700;color:#fff">
                <?php if($rec->avatar): ?>
                  <img src="<?php echo e(asset('storage/'.$rec->avatar)); ?>"
                       style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($rec->name,0,1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:14px;font-weight:600;color:var(--p-text)">
                  <?php echo e($rec->name); ?> <?php echo e($rec->lastname); ?>

                </div>
                <div style="font-size:12px;color:var(--p-hint);font-family:'DM Mono',monospace">
                  <?php echo e($rec->phone_number); ?>

                </div>
              </div>
            </div>
            <a href="<?php echo e(route('panel.users.show',$rec->id)); ?>"
               class="btn-p ghost sm" style="width:100%;justify-content:center">
              <i class="bi bi-person-lines-fill"></i> Profil
            </a>
            <?php else: ?>
            
            <div style="display:flex;flex-direction:column;gap:10px">
              <?php if($giftCertificate->recipient_name || $giftCertificate->recipient_phone): ?>
              <?php $__currentLoopData = [
                ['Ism',    $giftCertificate->recipient_name    ?? '—'],
                ['Telefon', $giftCertificate->recipient_phone  ?? '—'],
              ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div>
                <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                            letter-spacing:.07em;margin-bottom:2px"><?php echo e($k); ?></div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text);
                            font-family:'DM Mono',monospace"><?php echo e($v); ?></div>
              </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <?php endif; ?>

              <?php if($giftCertificate->status === 'sent' && $giftCertificate->expires_at): ?>
              <div style="padding:10px;background:<?php echo e($giftCertificate->is_expired ? 'var(--p-danger-d)' : 'var(--p-warning-d)'); ?>;
                          border-radius:7px;font-size:12px;
                          color:<?php echo e($giftCertificate->is_expired ? 'var(--p-danger)' : 'var(--p-warning)'); ?>">
                <i class="bi bi-<?php echo e($giftCertificate->is_expired ? 'x-circle' : 'clock'); ?> me-1"></i>
                <?php if($giftCertificate->is_expired): ?>
                  Muddati o'tdi — bekor qilinadi
                <?php else: ?>
                  Ishlatish muddati: <?php echo e($giftCertificate->expires_at->format('d.m.Y')); ?>

                  (<?php echo e($giftCertificate->expires_at->diffForHumans()); ?>)
                <?php endif; ?>
              </div>
              <?php elseif($giftCertificate->status === 'pending_payment'): ?>
              <div style="padding:10px;background:var(--p-elevated);border-radius:7px;
                          font-size:12px;color:var(--p-hint)">
                <i class="bi bi-hourglass me-1"></i>
                To'lov kutilmoqda
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/gift-certificates/show.blade.php ENDPATH**/ ?>