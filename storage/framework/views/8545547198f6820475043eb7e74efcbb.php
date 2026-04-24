<?php $__env->startSection('title', 'Post #'.$bookClub->id); ?>
<?php $__env->startSection('page-title', 'Post #'.$bookClub->id); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.book-club.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.book-club.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Post #<?php echo e($bookClub->id); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> 
    <p class="page-sub">
      <?php echo e($bookClub->created_at?->format('d.m.Y H:i')); ?>

      <?php if($bookClub->repost): ?>
        · <span style="color:var(--p-info)"><i class="bi bi-repeat"></i> Repost</span>
      <?php endif; ?>
    </p>
   <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex flex-wrap gap-2">
      <a href="<?php echo e(route('admin.book-club.moderation-queue')); ?>" class="btn-p ghost">
        <i class="bi bi-shield-exclamation"></i> UGC navbati
      </a>
      <a href="<?php echo e(route('admin.book-club.edit', $bookClub)); ?>" class="btn-p ghost">
        <i class="bi bi-pencil"></i> Tahrirlash
      </a>
      <form method="POST" action="<?php echo e(route('admin.book-club.destroy', $bookClub)); ?>"
            onsubmit="return confirm('Post o\'chirilsinmi?')">
        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
        <button class="btn-p danger ghost"><i class="bi bi-trash"></i> O'chirish</button>
      </form>
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

  
  <div class="xl:col-span-8">

    
    <div class="p-card mb-3 fade-up">
      <div class="dash-card-body">

        
        <div class="flex items-center gap-3 mb-3">
          <a href="<?php echo e(route('admin.users.show', $bookClub->user_id)); ?>"
             style="width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;
                    background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:block">
            <?php if($bookClub->user?->avatar): ?>
              <img src="<?php echo e($bookClub->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                          font-size:18px;font-weight:700;color:#fff">
                <?php echo e(strtoupper(substr($bookClub->user?->name ?? 'U', 0, 1))); ?>

              </div>
            <?php endif; ?>
          </a>
          <div>
            <a href="<?php echo e(route('admin.users.show', $bookClub->user_id)); ?>"
               style="font-size:15px;font-weight:600;color:var(--p-text);text-decoration:none">
              <?php echo e($bookClub->user?->name); ?> <?php echo e($bookClub->user?->lastname); ?>

            </a>
            <div style="font-size:12px;color:var(--p-hint)">
              <?php echo e($bookClub->created_at?->format('d.m.Y H:i')); ?>

            </div>
          </div>

          <?php if($bookClub->repost && $bookClub->originalAuthor): ?>
          <div style="margin-left:auto;font-size:12px;color:var(--p-info);display:flex;align-items:center;gap:6px">
            <i class="bi bi-repeat"></i>
            <span>Repost:
              <a href="<?php echo e(route('admin.users.show', $bookClub->reposted_user_id)); ?>"
                 style="color:var(--p-info);font-weight:600">
                <?php echo e($bookClub->originalAuthor->name); ?>

              </a>
            </span>
          </div>
          <?php endif; ?>
        </div>

        
        <?php if($bookClub->text): ?>
        <div style="font-size:14px;color:var(--p-text);line-height:1.8;white-space:pre-line;margin-bottom:16px">
          <?php echo e($bookClub->text); ?>

        </div>
        <?php endif; ?>

        
        <?php if($bookClub->images->count()): ?>
        <div class="flex flex-wrap gap-2 mb-4">
          <?php $__currentLoopData = $bookClub->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div style="position:relative">
            <a href="<?php echo e(asset('storage/'.$img->image)); ?>" target="_blank"
               style="display:block;width:100px;height:100px;border-radius:8px;
                      overflow:hidden;border:1px solid var(--p-border)">
              <img src="<?php echo e(asset('storage/'.$img->image)); ?>"
                   style="width:100%;height:100%;object-fit:cover">
            </a>
            <form method="POST"
                  action="<?php echo e(route('admin.book-club.image.delete', $img)); ?>"
                  style="position:absolute;top:4px;right:4px"
                  onsubmit="return confirm('Rasm o\'chirilsinmi?')">
              <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
              <button style="width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,.6);
                             border:none;color:#fff;font-size:10px;cursor:pointer;
                             display:flex;align-items:center;justify-content:center">
                <i class="bi bi-x"></i>
              </button>
            </form>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        
        <?php if($bookClub->product_id): ?>
        <div style="background:var(--p-elevated);border-radius:10px;padding:12px 14px;margin-bottom:12px">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px;text-transform:uppercase;letter-spacing:.07em">
            Bog'liq mahsulot
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <i class="bi bi-<?php echo e($bookClub->product_type === 'book' ? 'book' : 'pencil-square'); ?>"
               style="color:var(--p-accent);font-size:16px"></i>
            <span style="font-size:13px;font-weight:500;color:var(--p-text)">
              <?php echo e(ucfirst($bookClub->product_type)); ?> ID: #<?php echo e($bookClub->product_id); ?>

            </span>
          </div>
        </div>
        <?php endif; ?>

        
        <?php if($bookClub->votes->count()): ?>
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;margin-bottom:12px">
          <div style="font-size:12px;color:var(--p-hint);margin-bottom:10px;
                      font-weight:600;text-transform:uppercase;letter-spacing:.07em">
            <i class="bi bi-bar-chart mr-1"></i> So'rovnoma · <?php echo e($totalVotes); ?> ta ovoz
          </div>
          <?php $__currentLoopData = $bookClub->votes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $vCount = \DB::table('book_club_voted_users')->where('option_id', $vote->id)->count();
            $pct = $totalVotes > 0 ? round($vCount / $totalVotes * 100) : 0;
          ?>
          <div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="font-size:13px;color:var(--p-text)"><?php echo e($vote->option_text); ?></span>
              <span style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--p-accent)">
                <?php echo e($vCount); ?> (<?php echo e($pct); ?>%)
              </span>
            </div>
            <div class="dash-prog-track">
              <div class="dash-prog-fill"
                   style="width:<?php echo e($pct); ?>%;background:var(--p-accent)"></div>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        
        <?php if($bookClub->kangaroo_post_ugc_status || $bookClub->kangaroo_post_star !== null || $bookClub->kangaroo_post_checked_at): ?>
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;margin-bottom:12px;border:1px solid var(--p-border)">
          <div style="font-size:12px;font-weight:600;color:var(--p-hint);margin-bottom:10px;text-transform:uppercase;letter-spacing:.07em">
            <i class="bi bi-stars mr-1"></i> Kangaroo · post matni
          </div>
          <div style="font-size:13px;color:var(--p-text);display:flex;flex-wrap:wrap;gap:12px;margin-bottom:10px">
            <?php if($bookClub->kangaroo_post_ugc_status): ?>
              <span>Holat:
                <?php if($bookClub->kangaroo_post_ugc_status === 'pending_admin'): ?>
                  <strong style="color:var(--p-warning)">admin navbati</strong>
                <?php elseif($bookClub->kangaroo_post_ugc_status === 'admin_scored'): ?>
                  <strong style="color:var(--p-accent)">admin bahosi</strong>
                <?php else: ?>
                  <strong><?php echo e($bookClub->kangaroo_post_ugc_status); ?></strong>
                <?php endif; ?>
              </span>
            <?php endif; ?>
            <?php if($bookClub->kangaroo_post_star !== null): ?>
              <span>Matnga nisbatan baho: <strong><?php echo e(number_format((float) $bookClub->kangaroo_post_star, 2)); ?></strong> / 5</span>
            <?php endif; ?>
            <?php if($bookClub->kangaroo_post_checked_at): ?>
              <span style="color:var(--p-hint)">Tekshirilgan: <?php echo e($bookClub->kangaroo_post_checked_at->format('d.m.Y H:i')); ?></span>
            <?php endif; ?>
          </div>
          <?php if($bookClub->kangaroo_post_ugc_status === 'pending_admin'): ?>
          <form method="POST" action="<?php echo e(route('admin.book-club.post-ugc-score', $bookClub)); ?>" class="flex flex-wrap items-end gap-2">
            <?php echo csrf_field(); ?>
            <label style="font-size:12px;color:var(--p-hint)">Admin bahosi (1–5)</label>
            <select name="star" class="p-form-control" style="width:88px" required>
              <?php for($s = 1; $s <= 5; $s++): ?>
                <option value="<?php echo e($s); ?>"><?php echo e($s); ?> ★</option>
              <?php endfor; ?>
            </select>
            <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
          </form>
          <?php endif; ?>
        </div>
        <?php elseif($bookClub->text): ?>
        <div style="background:var(--p-elevated);border-radius:10px;padding:12px 14px;margin-bottom:12px;font-size:12px;color:var(--p-hint)">
          <i class="bi bi-stars mr-1"></i> Kangaroo tekshiruvi hali yozilmagan (sinxron yoki cron: <code>kangaroo:sync-content-moderation</code>).
        </div>
        <?php endif; ?>

        
        <div style="display:flex;gap:20px;padding-top:12px;border-top:1px solid var(--p-border)">
          <?php $__currentLoopData = [
            ['bi-heart-fill','danger', $likesCount, 'like'],
            ['bi-chat-fill', 'accent', $comments->total(), 'izoh'],
            ['bi-repeat',    'info',   $repostsCount, 'repost'],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon,$clr,$cnt,$lbl]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div style="display:flex;align-items:center;gap:5px">
            <i class="bi <?php echo e($icon); ?>" style="color:var(--p-<?php echo e($clr); ?>)"></i>
            <span style="font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
              <?php echo e($cnt); ?>

            </span>
            <span style="font-size:11px;color:var(--p-hint)"><?php echo e($lbl); ?></span>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    </div>

    
    <div class="p-card fade-up">
      <div class="dash-card-head">
        <div class="dash-card-title">Izohlar</div>
        <div class="dash-card-sub"><?php echo e($comments->total()); ?> ta</div>
      </div>
      <div class="dash-card-body p-0">

        <?php $__empty_1 = true; $__currentLoopData = $comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">

          
          <div class="flex gap-3">
            <a href="<?php echo e(route('admin.users.show', $comment->user_id)); ?>"
               style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:block">
              <?php if($comment->user?->avatar): ?>
                <img src="<?php echo e($comment->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                            font-size:12px;font-weight:700;color:#fff">
                  <?php echo e(strtoupper(substr($comment->user?->name ?? 'U', 0, 1))); ?>

                </div>
              <?php endif; ?>
            </a>
            <div style="flex:1;min-width:0">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <a href="<?php echo e(route('admin.users.show', $comment->user_id)); ?>"
                     style="font-size:13px;font-weight:600;color:var(--p-text);text-decoration:none">
                    <?php echo e($comment->user?->name); ?> <?php echo e($comment->user?->lastname); ?>

                  </a>
                  <span style="font-size:11px;color:var(--p-hint);margin-left:8px">
                    <?php echo e($comment->created_at?->format('d.m.Y H:i')); ?>

                  </span>
                </div>
                <div class="flex gap-1">
                  
                  <button class="btn-p ghost sm" title="Tahrirlash"
                          onclick="editComment(<?php echo e($comment->id); ?>, `<?php echo e(addslashes($comment->content)); ?>`)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST"
                        action="<?php echo e(route('admin.book-club.comment.delete', $comment)); ?>"
                        onsubmit="return confirm('Izoh o\'chirilsinmi?')">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </div>
              <p style="font-size:13px;color:var(--p-muted);margin:6px 0 8px;
                        line-height:1.6;white-space:pre-line"><?php echo e($comment->content); ?></p>
              <div style="font-size:11px;color:var(--p-hint);display:flex;gap:12px">
                <span>
                  <i class="bi bi-heart-fill" style="color:var(--p-danger);font-size:10px"></i>
                  <?php echo e($comment->likes_count ?? $comment->likes->count()); ?>

                </span>
                <?php if($comment->replies_count > 0): ?>
                <span style="color:var(--p-accent)">
                  <i class="bi bi-chat"></i> <?php echo e($comment->replies_count); ?> ta javob
                </span>
                <?php endif; ?>
              </div>

              <?php if($comment->kangaroo_ugc_status || $comment->kangaroo_star_equivalent !== null || $comment->kangaroo_toxicity !== null || $comment->kangaroo_checked_at): ?>
              <div style="margin-top:10px;padding:10px 12px;background:var(--p-elevated);border-radius:8px;border:1px solid var(--p-border)">
                <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Kangaroo · izoh</div>
                <div style="font-size:12px;color:var(--p-muted);display:flex;flex-wrap:wrap;gap:10px;margin-bottom:8px">
                  <span>Holat:
                    <?php if($comment->kangaroo_ugc_status === 'pending_admin'): ?>
                      <strong style="color:var(--p-warning)">admin navbati</strong>
                    <?php elseif($comment->kangaroo_ugc_status === 'admin_scored'): ?>
                      <strong style="color:var(--p-accent)">admin bahosi</strong>
                    <?php else: ?>
                      <strong style="color:var(--p-text)"><?php echo e($comment->kangaroo_ugc_status ?? '—'); ?></strong>
                    <?php endif; ?>
                  </span>
                  <?php if($comment->kangaroo_star_equivalent !== null): ?>
                    <span>Izohga nisbatan baho: <strong style="color:var(--p-text)"><?php echo e(number_format((float) $comment->kangaroo_star_equivalent, 2)); ?></strong> / 5</span>
                  <?php endif; ?>
                  <?php if($comment->kangaroo_toxicity !== null): ?>
                    <span>Toxicity: <strong style="color:var(--p-text)"><?php echo e(number_format((float) $comment->kangaroo_toxicity, 3)); ?></strong></span>
                  <?php endif; ?>
                </div>
                <?php if($comment->kangaroo_ugc_status === 'pending_admin'): ?>
                <form method="POST" action="<?php echo e(route('admin.book-club.comment.ugc-score', $comment)); ?>" class="flex flex-wrap items-end gap-2">
                  <?php echo csrf_field(); ?>
                  <label style="font-size:11px;color:var(--p-hint)">Admin bahosi (1–5)</label>
                  <select name="star" class="p-form-control" style="width:88px" required>
                    <?php for($s = 1; $s <= 5; $s++): ?>
                      <option value="<?php echo e($s); ?>"><?php echo e($s); ?> ★</option>
                    <?php endfor; ?>
                  </select>
                  <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
                </form>
                <?php endif; ?>
              </div>
              <?php endif; ?>

              
              <?php if($comment->replies->count()): ?>
              <div style="margin-top:12px;padding-left:16px;border-left:2px solid var(--p-border)">
                <?php $__currentLoopData = $comment->replies->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reply): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;gap:10px;margin-bottom:10px">
                  <a href="<?php echo e(route('admin.users.show', $reply->user_id)); ?>"
                     style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                            background:linear-gradient(135deg,#7c5cfc,var(--p-accent));display:block">
                    <?php if($reply->user?->avatar): ?>
                      <img src="<?php echo e($reply->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                      <div style="width:100%;height:100%;display:flex;align-items:center;
                                  justify-content:center;font-size:11px;font-weight:700;color:#fff">
                        <?php echo e(strtoupper(substr($reply->user?->name ?? 'U', 0, 1))); ?>

                      </div>
                    <?php endif; ?>
                  </a>
                  <div style="flex:1">
                    <div class="flex items-center justify-between">
                      <div>
                        <a href="<?php echo e(route('admin.users.show', $reply->user_id)); ?>"
                           style="font-size:12px;font-weight:600;color:var(--p-text);text-decoration:none">
                          <?php echo e($reply->user?->name); ?>

                        </a>
                        <span style="font-size:10px;color:var(--p-hint);margin-left:6px">
                          <?php echo e($reply->created_at?->format('d.m.Y H:i')); ?>

                        </span>
                      </div>
                      <form method="POST"
                            action="<?php echo e(route('admin.book-club.comment.delete', $reply)); ?>"
                            onsubmit="return confirm('Javob o\'chirilsinmi?')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button class="btn-p danger sm" style="padding:2px 6px">
                          <i class="bi bi-trash" style="font-size:10px"></i>
                        </button>
                      </form>
                    </div>
                    <p style="font-size:12px;color:var(--p-muted);margin:4px 0;line-height:1.5">
                      <?php echo e($reply->content); ?>

                    </p>
                    <div style="font-size:10px;color:var(--p-hint)">
                      <i class="bi bi-heart-fill" style="color:var(--p-danger);font-size:9px"></i>
                      <?php echo e($reply->likes->count()); ?>

                    </div>
                  </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($comment->replies_count > 3): ?>
                <div style="font-size:11px;color:var(--p-hint);padding-left:8px">
                  va yana <?php echo e($comment->replies_count - 3); ?> ta javob...
                </div>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:30px;color:var(--p-hint)">
          <i class="bi bi-chat-square" style="font-size:28px;display:block;margin-bottom:8px"></i>
          Izohlar yo'q
        </div>
        <?php endif; ?>

      </div>
      <?php echo e($comments->links('a122.partials.pagination')); ?>

    </div>

  </div>

  
  <div class="xl:col-span-4">

    
    <div class="p-card mb-3 fade-up">
      <div class="dash-card-head"><div class="dash-card-title">Ma'lumotlar</div></div>
      <div class="dash-card-body">
        <?php $__currentLoopData = [
          ['Post ID',      '#'.$bookClub->id],
          ['Tur',          $bookClub->repost ? 'Repost' : 'Original post'],
          ['Yaratildi',    $bookClub->created_at?->format('d.m.Y H:i')],
          ['Mahsulot tur', $bookClub->product_type ? ucfirst($bookClub->product_type) : '—'],
          ['Mahsulot ID',  $bookClub->product_id ? '#'.$bookClub->product_id : '—'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

    
    <div class="p-card mb-3 fade-up">
      <div class="dash-card-head">
        <div class="dash-card-title">
          <i class="bi bi-heart-fill mr-1" style="color:var(--p-danger)"></i> Like bosganlar
        </div>
        <span class="s-pill danger"><?php echo e($likesCount); ?></span>
      </div>
      <div class="dash-card-body">
        <?php $__empty_1 = true; $__currentLoopData = $likers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $like): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <a href="<?php echo e(route('admin.users.show', $like->user_id)); ?>"
             style="width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;
                    background:linear-gradient(135deg,var(--p-danger),#ff8fab);display:block">
            <?php if($like->user?->avatar): ?>
              <img src="<?php echo e($like->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                <?php echo e(strtoupper(substr($like->user?->name ?? 'U', 0, 1))); ?>

              </div>
            <?php endif; ?>
          </a>
          <div style="flex:1;min-width:0">
            <a href="<?php echo e(route('admin.users.show', $like->user_id)); ?>"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">
              <?php echo e($like->user?->name); ?> <?php echo e($like->user?->lastname); ?>

            </a>
            <span style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              #<?php echo e($like->user_id); ?>

            </span>
          </div>
          <span style="font-size:10px;color:var(--p-hint);white-space:nowrap">
            <?php echo e($like->created_at?->format('d.m')); ?>

          </span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:20px;color:var(--p-hint);font-size:13px">
          Like yo'q
        </div>
        <?php endif; ?>
        <?php if($likesCount > 20): ?>
          <div style="font-size:11px;color:var(--p-hint);padding-top:8px;text-align:center">
            va yana <?php echo e($likesCount - 20); ?> ta...
          </div>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card fade-up">
      <div class="dash-card-head">
        <div class="dash-card-title">
          <i class="bi bi-repeat mr-1" style="color:var(--p-info)"></i> Repost qilganlar
        </div>
        <span class="s-pill info"><?php echo e($repostsCount); ?></span>
      </div>
      <div class="dash-card-body">
        <?php $__empty_1 = true; $__currentLoopData = $reposters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <a href="<?php echo e(route('admin.users.show', $rp->user_id)); ?>"
             style="width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;
                    background:linear-gradient(135deg,var(--p-info),#0ea5e9);display:block">
            <?php if($rp->user?->avatar): ?>
              <img src="<?php echo e($rp->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                <?php echo e(strtoupper(substr($rp->user?->name ?? 'U', 0, 1))); ?>

              </div>
            <?php endif; ?>
          </a>
          <div style="flex:1;min-width:0">
            <a href="<?php echo e(route('admin.users.show', $rp->user_id)); ?>"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">
              <?php echo e($rp->user?->name); ?> <?php echo e($rp->user?->lastname); ?>

            </a>
          </div>
          <a href="<?php echo e(route('admin.book-club.show', $rp)); ?>" class="btn-p ghost sm">
            <i class="bi bi-eye"></i>
          </a>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:20px;color:var(--p-hint);font-size:13px">
          Repostlar yo'q
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>


<div id="editCommentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
     z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border-radius:14px;padding:24px;width:100%;max-width:480px;
              border:1px solid var(--p-border)">
    <div style="font-size:16px;font-weight:600;color:var(--p-text);margin-bottom:16px">
      <i class="bi bi-pencil mr-1"></i> Izohni tahrirlash
    </div>
    <form method="POST" id="editCommentForm">
      <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
      <textarea name="content" id="editCommentContent" rows="4"
                class="p-form-control" style="width:100%;margin-bottom:12px"
                required></textarea>
      <div class="flex gap-2 justify-end">
        <button type="button" class="btn-p ghost"
                onclick="closeEditModal()">Bekor qilish</button>
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
      </div>
    </form>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function editComment(id, content) {
  const modal = document.getElementById('editCommentModal');
  document.getElementById('editCommentContent').value = content;
  document.getElementById('editCommentForm').action =
    `/panel/book-club/comments/${id}`;
  modal.style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editCommentModal').style.display = 'none';
}

document.getElementById('editCommentModal').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/book-club/show.blade.php ENDPATH**/ ?>