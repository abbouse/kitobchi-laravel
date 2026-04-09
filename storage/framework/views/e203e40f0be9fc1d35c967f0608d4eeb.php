<?php $__env->startSection('title', $reel->title); ?>
<?php $__env->startSection('page-title', $reel->title); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="<?php echo e(route('panel.reels.index')); ?>" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title"><?php echo e($reel->title); ?></h1>
      <p class="page-sub">
        Tartib: <?php echo e($reel->order); ?>

        · <?php echo e($reel->items->count()); ?> ta video
        · Yaratildi: <?php echo e($reel->created_at?->format('d.m.Y')); ?>

      </p>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo e(route('panel.reels.edit', $reel)); ?>" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
    <form method="POST" action="<?php echo e(route('panel.reels.destroy', $reel)); ?>"
          onsubmit="return confirm('Reel va BARCHA videolari o\'chirilsinmi?')">
      <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
      <button class="btn-p danger">
        <i class="bi bi-trash"></i> O'chirish
      </button>
    </form>
  </div>
</div>

<div class="row g-3">

  
  <div class="col-xl-4">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Ma'lumotlar</div>
      </div>
      <div style="padding:0 18px 18px">
        <?php $__currentLoopData = [
          ['Sarlavha', $reel->title],
          ['Tartib',   $reel->order],
          ['Videolar', $reel->items->count().' ta'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k, $v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($reel->description): ?>
        <div style="margin-top:12px">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:5px">Tavsif</div>
          <div style="font-size:13px;color:var(--p-muted);line-height:1.7">
            <?php echo e($reel->description); ?>

          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  
  <div class="col-xl-8">

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-play-circle me-1" style="color:var(--p-accent)"></i>
          Videolar
        </div>
        <span class="s-pill accent" style="font-size:11px">
          <?php echo e($reel->items->count()); ?> ta
        </span>
      </div>

      <?php $__empty_1 = true; $__currentLoopData = $reel->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div style="display:flex;align-items:center;gap:12px;
                  padding:14px 18px;border-top:1px solid var(--p-border)"
           id="item-<?php echo e($item->id); ?>">

        
        <div class="drag-handle" style="color:var(--p-hint);cursor:grab;padding:4px;
                    font-size:16px" title="Tartiblash">
          <i class="bi bi-grip-vertical"></i>
        </div>

        
        <div style="width:28px;height:28px;border-radius:7px;background:var(--p-elevated);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;
                    font-family:'DM Mono',monospace;font-size:12px;font-weight:600;
                    color:var(--p-accent)">
          <?php echo e($item->order); ?>

        </div>

        
        <div style="flex:1;display:flex;gap:8px;flex-wrap:wrap">
          <?php $__currentLoopData = [
            ['720p', $item->video_720p, 'success'],
            ['480p', $item->video_480p, 'info'],
            ['360p', $item->video_360p, 'warning'],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $path, $color]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php if($path): ?>
          <a href="<?php echo e(asset('storage/'.$path)); ?>" target="_blank"
             class="s-pill <?php echo e($color); ?>"
             style="font-size:10px;text-decoration:none;cursor:pointer"
             title="<?php echo e($path); ?>">
            <i class="bi bi-play-fill"></i> <?php echo e($label); ?>

          </a>
          <?php else: ?>
          <span class="s-pill muted" style="font-size:10px">
            <i class="bi bi-dash"></i> <?php echo e($label); ?>

          </span>
          <?php endif; ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <?php if($item->video_720p): ?>
        <button onclick="previewVideo('<?php echo e(asset('storage/'.$item->video_720p)); ?>')"
                class="btn-p ghost sm" title="Ko'rish">
          <i class="bi bi-eye"></i>
        </button>
        <?php endif; ?>

        
        <form method="POST"
              action="<?php echo e(route('panel.reels.items.destroy', [$reel, $item])); ?>"
              onsubmit="return confirm('Video o\'chirilsinmi?')">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
          <button class="btn-p danger sm">
            <i class="bi bi-trash"></i>
          </button>
        </form>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div style="padding:36px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-camera-video" style="font-size:28px;display:block;margin-bottom:8px"></i>
        Hali video qo'shilmagan
      </div>
      <?php endif; ?>
    </div>

    
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-cloud-upload me-1" style="color:var(--p-success)"></i>
          Yangi video qo'shish
        </div>
      </div>
      <form method="POST"
            action="<?php echo e(route('panel.reels.items.store', $reel)); ?>"
            enctype="multipart/form-data"
            id="uploadForm">
        <?php echo csrf_field(); ?>
        <div style="padding:0 18px 18px">

          <div class="row g-3 mb-3">
            
            <div class="col-md-4">
              <?php echo $__env->make('panel.reels._video-upload', [
                'field' => 'video_720p',
                'label' => '720p (HD)',
                'color' => 'success',
                'required' => true,
                'maxMb' => 200,
              ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            
            <div class="col-md-4">
              <?php echo $__env->make('panel.reels._video-upload', [
                'field' => 'video_480p',
                'label' => '480p',
                'color' => 'info',
                'required' => false,
                'maxMb' => 100,
              ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            
            <div class="col-md-4">
              <?php echo $__env->make('panel.reels._video-upload', [
                'field' => 'video_360p',
                'label' => '360p',
                'color' => 'warning',
                'required' => false,
                'maxMb' => 50,
              ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
          </div>

          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="p-form-label">
                Tartib <span style="color:var(--p-danger)">*</span>
              </label>
              <input type="number" name="order" class="p-form-control"
                     value="<?php echo e(($reel->items->max('order') ?? 0) + 1); ?>"
                     min="1" required>
            </div>
            <div class="col-md-9">
              
              <div id="uploadProgress" style="display:none;margin-bottom:10px">
                <div style="font-size:12px;color:var(--p-muted);margin-bottom:5px">
                  Yuklanmoqda... <span id="progressPct">0</span>%
                </div>
                <div style="background:var(--p-elevated);border-radius:4px;height:6px;overflow:hidden">
                  <div id="progressBar"
                       style="height:100%;background:var(--p-accent);
                              border-radius:4px;width:0%;transition:width .3s"></div>
                </div>
              </div>
              <button type="submit" class="btn-p success" id="uploadBtn"
                      style="width:100%;justify-content:center">
                <i class="bi bi-cloud-upload"></i> Video yuklash
              </button>
            </div>
          </div>

        </div>
      </form>
    </div>

  </div>
</div>


<div id="videoModal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);
            z-index:9999;align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--p-surface);border-radius:14px;padding:16px;
              width:100%;max-width:680px;border:1px solid var(--p-border)">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div style="font-size:14px;font-weight:600;color:var(--p-text)">Video preview</div>
      <button onclick="closeVideo()"
              style="background:none;border:none;cursor:pointer;
                     color:var(--p-muted);font-size:20px">×</button>
    </div>
    <video id="videoPlayer" controls
           style="width:100%;border-radius:8px;background:#000;max-height:60vh">
    </video>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Preview
function previewVideo(url) {
  const modal  = document.getElementById('videoModal');
  const player = document.getElementById('videoPlayer');
  player.src   = url;
  modal.style.display = 'flex';
  player.play().catch(()=>{});
}
function closeVideo() {
  const player = document.getElementById('videoPlayer');
  player.pause(); player.src = '';
  document.getElementById('videoModal').style.display = 'none';
}
document.getElementById('videoModal')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) closeVideo();
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeVideo();
});

// Upload progress (XHR bilan)
const form    = document.getElementById('uploadForm');
const progDiv = document.getElementById('uploadProgress');
const progBar = document.getElementById('progressBar');
const progPct = document.getElementById('progressPct');
const btn     = document.getElementById('uploadBtn');

form?.addEventListener('submit', function(e) {
  e.preventDefault();

  const fd  = new FormData(form);
  const xhr = new XMLHttpRequest();

  xhr.upload.addEventListener('progress', (ev) => {
    if (ev.lengthComputable) {
      const pct = Math.round(ev.loaded / ev.total * 100);
      progDiv.style.display = 'block';
      progBar.style.width   = pct + '%';
      progPct.textContent   = pct;
    }
  });

  xhr.addEventListener('load', () => {
    if (xhr.status === 302 || xhr.status === 200) {
      window.location.reload();
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Video yuklash';
      progDiv.style.display = 'none';
      alert('Xatolik yuz berdi. Qayta urinib ko\'ring.');
    }
  });

  xhr.addEventListener('error', () => {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Video yuklash';
    progDiv.style.display = 'none';
  });

  btn.disabled   = true;
  btn.innerHTML  = '<i class="bi bi-hourglass-split"></i> Yuklanmoqda...';
  progDiv.style.display = 'block';

  xhr.open('POST', form.action);
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.send(fd);
});

// Drag-and-drop tartib
const itemsContainer = document.querySelector('.p-card');
let dragEl = null;

document.querySelectorAll('.drag-handle').forEach(handle => {
  const row = handle.closest('[id^="item-"]');
  row.draggable = true;

  row.addEventListener('dragstart', () => {
    dragEl = row;
    setTimeout(() => row.style.opacity = '.4', 0);
  });
  row.addEventListener('dragend', () => {
    row.style.opacity = '';
    dragEl = null;
    saveOrder();
  });
  row.addEventListener('dragover', e => {
    e.preventDefault();
    if (dragEl && dragEl !== row) {
      const rect = row.getBoundingClientRect();
      const mid  = rect.top + rect.height / 2;
      row.parentNode.insertBefore(dragEl, e.clientY < mid ? row : row.nextSibling);
    }
  });
});

function saveOrder() {
  const ids = [...document.querySelectorAll('[id^="item-"]')]
    .map(el => el.id.replace('item-', ''));

  fetch('<?php echo e(route('panel.reels.items.reorder', $reel)); ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({ items: ids }),
  });
}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/reels/show.blade.php ENDPATH**/ ?>