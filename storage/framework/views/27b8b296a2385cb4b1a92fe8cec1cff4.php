<?php $__env->startSection('title', 'AI Chat — '.$conversation->name.' '.$conversation->lastname); ?>
<?php $__env->startSection('page-title', 'AI Chat'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="<?php echo e(route('panel.chats.index', ['tab'=>'ai'])); ?>" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">AI Chat</h1>
    <p class="page-sub">
      <?php echo e($conversation->name); ?> <?php echo e($conversation->lastname); ?>

      · <?php echo e($conversation->phone_number); ?>

    </p>
  </div>
</div>

<div class="row g-3">

  
  <div class="col-xl-3">
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Foydalanuvchi</div></div>
      <div style="padding:14px 18px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
          <div style="width:46px;height:46px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:18px;font-weight:700;color:#fff">
            <?php if($conversation->avatar): ?>
              <img src="<?php echo e($conversation->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($conversation->name??'U',0,1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($conversation->name); ?> <?php echo e($conversation->lastname); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint);font-family:'DM Mono',monospace">
              <?php echo e($conversation->phone_number); ?>

            </div>
          </div>
        </div>
        <a href="<?php echo e(route('panel.users.show', $conversation->user_id)); ?>"
           class="btn-p ghost" style="width:100%;justify-content:center;font-size:12px">
          <i class="bi bi-person"></i> Profil
        </a>
      </div>
    </div>

    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Statistika</div></div>
      <div style="padding:0 18px 14px">
        <div style="display:flex;justify-content:space-between;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">Jami xabarlar</span>
          <span style="font-size:12px;font-weight:600;font-family:'DM Mono',monospace;
                       color:var(--p-text)"><?php echo e($messages->total()); ?> ta</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0">
          <span style="font-size:12px;color:var(--p-hint)">So'nggi faollik</span>
          <span style="font-size:12px;color:var(--p-muted)">
            <?php echo e($conversation->updated_at
              ? \Carbon\Carbon::parse($conversation->updated_at)->diffForHumans()
              : '—'); ?>

          </span>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xl-9">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-robot me-1" style="color:var(--p-accent)"></i>
          AI suhbat tarixi
        </div>
      </div>
      <div style="padding:0 18px 18px;max-height:65vh;overflow-y:auto" id="ai-scroll">

        <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          // chat_messages jadval: is_ai = true (bot), false (user)
          $isUser = !($msg->is_ai ?? false);
        ?>
        <div style="display:flex;gap:10px;margin-bottom:14px;
                    <?php echo e($isUser ? 'flex-direction:row-reverse' : ''); ?>">

          
          <div style="width:30px;height:30px;border-radius:50%;flex-shrink:0;margin-top:2px;
                      background:<?php echo e($isUser
                        ? 'linear-gradient(135deg,var(--p-accent),#7c5cfc)'
                        : 'linear-gradient(135deg,#14b8a6,#06b6d4)'); ?>;
                      display:flex;align-items:center;justify-content:center;
                      font-size:13px;color:#fff">
            <?php if($isUser): ?>
              <?php echo e(strtoupper(substr($conversation->name??'U',0,1))); ?>

            <?php else: ?>
              <i class="bi bi-robot"></i>
            <?php endif; ?>
          </div>

          
          <div style="max-width:72%;
                      <?php echo e($isUser ? 'align-items:flex-end;display:flex;flex-direction:column' : ''); ?>">
            <div style="font-size:10px;color:var(--p-hint);margin-bottom:4px;font-weight:500;
                        <?php echo e($isUser ? 'text-align:right' : ''); ?>">
              <?php echo e($isUser ? ($conversation->name ?? 'User') : '🤖 AI Yordamchi'); ?>

            </div>
            <div style="background:<?php echo e($isUser
              ? 'rgba(79,124,255,.1)'
              : 'var(--p-elevated)'); ?>;
                        border:1px solid var(--p-border);
                        border-radius:<?php echo e($isUser ? '12px 4px 12px 12px' : '4px 12px 12px 12px'); ?>;
                        padding:11px 14px">
              <div style="font-size:13px;color:var(--p-text);line-height:1.7;
                          word-break:break-word;white-space:pre-wrap">
                <?php echo e($msg->message ?? ''); ?>

              </div>
            </div>

            
            <?php if(!$isUser && isset($msg->items) && $msg->items->count()): ?>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;max-width:480px">
              <?php $__currentLoopData = $msg->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $isBook = $item->type === 'book';
                $route  = $isBook
                  ? route('panel.books.show', $item->id)
                  : route('panel.stationery.show', $item->id);
              ?>
              <a href="<?php echo e($route); ?>" target="_blank"
                 style="display:flex;align-items:center;gap:8px;
                        background:var(--p-surface);border:1px solid var(--p-border);
                        border-radius:10px;padding:7px 10px;text-decoration:none;
                        min-width:160px;max-width:220px;transition:border-color .12s"
                 onmouseover="this.style.borderColor='var(--p-border2)'"
                 onmouseout="this.style.borderColor='var(--p-border)'">

                
                <div style="width:<?php echo e($isBook ? '30px' : '36px'); ?>;
                            height:<?php echo e($isBook ? '42px' : '36px'); ?>;
                            border-radius:<?php echo e($isBook ? '4px' : '8px'); ?>;
                            overflow:hidden;flex-shrink:0;background:var(--p-elevated);
                            display:flex;align-items:center;justify-content:center">
                  <?php if($item->image): ?>
                    <img src="<?php echo e($item->image); ?>"
                         style="width:100%;height:100%;object-fit:cover">
                  <?php else: ?>
                    <i class="bi bi-<?php echo e($isBook ? 'book' : 'box'); ?>"
                       style="color:var(--p-hint);font-size:12px"></i>
                  <?php endif; ?>
                </div>

                
                <div style="min-width:0;flex:1">
                  <div style="font-size:11.5px;font-weight:600;color:var(--p-text);
                              white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?php echo e(Str::limit($item->name, 22)); ?>

                  </div>
                  <?php if($isBook && $item->author): ?>
                  <div style="font-size:10px;color:var(--p-hint);
                              white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?php echo e($item->author); ?>

                  </div>
                  <?php else: ?>
                  <span class="s-pill <?php echo e($isBook ? 'info' : 'warning'); ?>"
                        style="font-size:9px;padding:1px 6px">
                    <?php echo e($isBook ? 'Kitob' : 'Kanstovar'); ?>

                  </span>
                  <?php endif; ?>
                  <div style="font-size:10px;color:var(--p-success);font-weight:600;
                              font-family:'DM Mono',monospace;margin-top:2px">
                    <?php echo e(number_format($item->price)); ?> UZS
                  </div>
                </div>
              </a>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
            <div style="font-size:10px;color:var(--p-hint);margin-top:3px;
                        font-family:'DM Mono',monospace;
                        <?php echo e($isUser ? 'text-align:right' : ''); ?>">
              <?php echo e(\Carbon\Carbon::parse($msg->created_at)->format('d.m H:i')); ?>

            </div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-robot" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Xabarlar yo'q
        </div>
        <?php endif; ?>
      </div>

      <?php if($messages->hasPages()): ?>
      <div style="border-top:1px solid var(--p-border);padding:10px 18px;
                  display:flex;justify-content:center">
        <?php echo e($messages->links('panel.partials.pagination')); ?>

      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const el = document.getElementById('ai-scroll');
if (el) el.scrollTop = el.scrollHeight;
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/chats/show-ai.blade.php ENDPATH**/ ?>