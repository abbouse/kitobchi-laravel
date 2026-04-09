


<div class="row g-3">

  
  <div class="col-xl-7">

    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Asosiy ma'lumotlar</div></div>
      <div style="padding:0 18px 18px">
        <div class="row g-3">

          <div class="col-12">
            <label class="p-form-label">
              Sarlavha <span style="color:var(--p-danger)">*</span>
            </label>
            <input type="text" name="title" class="p-form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('title', $marketNews?->title)); ?>"
                   placeholder="Banner sarlavhasi..." required>
            <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
              <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="col-12">
            <label class="p-form-label">Tavsif</label>
            <textarea name="description" class="p-form-control" rows="3"
                      placeholder="Ixtiyoriy tavsif..."><?php echo e(old('description', $marketNews?->description)); ?></textarea>
          </div>

          <div class="col-md-6">
            <label class="p-form-label">
              Joylashuv <span style="color:var(--p-danger)">*</span>
            </label>
            <select name="align" class="p-form-control" required>
              <option value="top"    <?php echo e(old('align', $marketNews?->align) === 'top'    ? 'selected' : ''); ?>>
                ⬆ Yuqorida
              </option>
              <option value="center" <?php echo e(old('align', $marketNews?->align) === 'center' ? 'selected' : ''); ?>>
                ↔ O'rtada
              </option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="p-form-label">Status</label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px">
              <input type="hidden" name="status" value="0">
              <input type="checkbox" name="status" value="1"
                     <?php echo e(old('status', $marketNews?->status) ? 'checked' : ''); ?>

                     style="width:18px;height:18px;accent-color:var(--p-accent)">
              <span style="font-size:13px;color:var(--p-text)">Faol (marketpleysda ko'rinadi)</span>
            </label>
          </div>

        </div>
      </div>
    </div>

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Action (Flutter uchun)</div>
        <div style="font-size:11px;color:var(--p-hint)">
          Banner bosilganda nima ochiladi?
        </div>
      </div>
      <div style="padding:0 18px 18px">

        
        <div class="row g-2 mb-3" id="actionBtns">
          <?php $__currentLoopData = [
            ['news',       'Yangilik',          'muted',   'bi-newspaper',   'Faqat matn/rasm ko\'rsatadi'],
            ['to_shop',    'Do\'konga o\'tish',  'warning', 'bi-shop-window', 'Seller do\'konini ochadi'],
            ['to_product', 'Mahsulotga o\'tish', 'info',    'bi-book',        'Kitob sahifasini ochadi'],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$val, $label, $color, $icon, $hint]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="col-4">
            <input type="radio" name="action" id="act-<?php echo e($val); ?>" value="<?php echo e($val); ?>"
                   <?php echo e(old('action', $marketNews?->action ?? 'news') === $val ? 'checked' : ''); ?>

                   style="display:none">
            <label for="act-<?php echo e($val); ?>" class="action-card"
                   style="display:flex;flex-direction:column;align-items:center;gap:7px;
                          padding:14px 8px;border-radius:10px;cursor:pointer;
                          border:2px solid var(--p-border);transition:all .15s;text-align:center">
              <i class="bi <?php echo e($icon); ?>"
                 style="font-size:22px;color:var(--p-<?php echo e($color); ?>)"></i>
              <div style="font-size:12px;font-weight:500;color:var(--p-text)"><?php echo e($label); ?></div>
              <div style="font-size:10px;color:var(--p-hint);line-height:1.4"><?php echo e($hint); ?></div>
            </label>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div id="actionIdBlock" style="<?php echo e(old('action', $marketNews?->action ?? 'news') === 'news' ? 'display:none' : ''); ?>">
          <div class="d-flex gap-2 align-items-end">
            <div style="flex:1">
              <label class="p-form-label" id="actionIdLabel">
                ID kiriting
              </label>
              <input type="number" name="action_id" id="actionIdInput"
                     class="p-form-control <?php $__errorArgs = ['action_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('action_id', $marketNews?->action_id)); ?>"
                     placeholder="Masalan: 42" min="1">
              <?php $__errorArgs = ['action_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
              <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <button type="button" onclick="previewAction()"
                    class="btn-p ghost" id="previewBtn"
                    style="white-space:nowrap">
              <i class="bi bi-search"></i> Tekshirish
            </button>
          </div>

          
          <div id="previewResult" style="margin-top:12px;display:none">
            
          </div>
        </div>

      </div>
    </div>

  </div>

  
  <div class="col-xl-5">
    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Banner rasmi</div></div>
      <div style="padding:0 18px 18px">

        
        <?php if($marketNews?->imgUrl): ?>
        <div style="margin-bottom:14px">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:7px">Hozirgi rasm:</div>
          <div style="border-radius:10px;overflow:hidden;background:var(--p-elevated);
                      max-height:160px">
            <img src="<?php echo e(asset('storage/'.$marketNews->imgUrl)); ?>"
                 style="width:100%;object-fit:cover;max-height:160px"
                 id="currentImg">
          </div>
        </div>
        <?php endif; ?>

        
        <div id="dropZone"
             style="border:2px dashed var(--p-border);border-radius:10px;
                    padding:28px 16px;text-align:center;cursor:pointer;
                    transition:border-color .2s"
             onclick="document.getElementById('imgInput').click()"
             ondragover="event.preventDefault();this.style.borderColor='var(--p-accent)'"
             ondragleave="this.style.borderColor='var(--p-border)'"
             ondrop="handleImgDrop(event)">
          <i class="bi bi-image"
             style="font-size:28px;color:var(--p-hint);display:block;margin-bottom:8px"></i>
          <div style="font-size:13px;color:var(--p-muted)">
            <?php echo e($marketNews?->imgUrl ? 'Yangi rasm tanlash' : 'Rasm tanlang yoki tashlang'); ?>

          </div>
          <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
            JPG, PNG, WebP · Maks 4MB
          </div>
          <div id="imgName"
               style="margin-top:10px;font-size:12px;color:var(--p-accent);
                      display:none;font-weight:500"></div>
        </div>

        <input type="file" id="imgInput" name="imgUrl"
               accept="image/jpg,image/jpeg,image/png,image/webp"
               style="display:none"
               onchange="showImgPreview(this)">

        
        <div id="imgPreview" style="margin-top:12px;display:none">
          <img id="imgPreviewEl"
               style="width:100%;border-radius:8px;max-height:160px;object-fit:cover">
        </div>

        <?php $__errorArgs = ['imgUrl'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div style="font-size:11px;color:var(--p-danger);margin-top:8px"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

      </div>
    </div>
  </div>

</div>


<div class="d-flex gap-2 fade-up mt-1">
  <button type="submit" class="btn-p primary">
    <i class="bi bi-check-lg"></i>
    <?php echo e($marketNews ? 'Saqlash' : 'Yaratish'); ?>

  </button>
  <a href="<?php echo e($marketNews
    ? route('panel.market-news.show', $marketNews)
    : route('panel.market-news.index')); ?>"
     class="btn-p ghost">Bekor</a>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
// ── Action card highlight ──────────────────────────────
const actionLabels = {
  to_shop:    'Do\'kon ID <span style="color:var(--p-hint);font-size:10px">(sellers jadvali)</span>',
  to_product: 'Kitob ID <span style="color:var(--p-hint);font-size:10px">(books jadvali)</span>',
};

function updateActionUI() {
  const selected = document.querySelector('input[name="action"]:checked')?.value || 'news';
  const block    = document.getElementById('actionIdBlock');
  const label    = document.getElementById('actionIdLabel');

  // Card highlight
  document.querySelectorAll('.action-card').forEach(card => {
    const radio = document.getElementById('act-' + card.getAttribute('for').replace('act-',''));
    const isActive = radio?.checked;
    card.style.borderColor  = isActive ? 'var(--p-accent)' : 'var(--p-border)';
    card.style.background   = isActive ? 'var(--p-accent-d)' : '';
  });

  // ID block
  block.style.display = selected === 'news' ? 'none' : '';
  if (label && actionLabels[selected]) {
    label.innerHTML = actionLabels[selected];
  }

  // Preview tozalash
  document.getElementById('previewResult').style.display = 'none';
}

document.querySelectorAll('input[name="action"]').forEach(r => {
  r.addEventListener('change', updateActionUI);
});
updateActionUI(); // initial

// ── Action preview (AJAX) ─────────────────────────────
async function previewAction() {
  const action = document.querySelector('input[name="action"]:checked')?.value;
  const id     = document.getElementById('actionIdInput')?.value;
  const result = document.getElementById('previewResult');
  const btn    = document.getElementById('previewBtn');

  if (!id || action === 'news') return;

  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Tekshirilmoqda...';
  btn.disabled  = true;

  try {
    const res  = await fetch(
      `/panel/market-news/preview-action?action=${action}&id=${id}`
    );
    const data = await res.json();

    if (!data.found) {
      result.style.display = 'block';
      result.innerHTML = `
        <div style="padding:12px;border-radius:8px;background:var(--p-danger-d);
                    border:1px solid rgba(255,92,106,.2);font-size:13px;
                    color:var(--p-danger)">
          <i class="bi bi-x-circle me-1"></i>
          ID <strong>${id}</strong> topilmadi
        </div>`;
      return;
    }

    const isShop   = data.type === 'shop';
    const imgStyle = 'width:42px;height:42px;border-radius:' + (isShop ? '8px' : '6px') +
                     ';object-fit:cover;flex-shrink:0;background:var(--p-elevated)';

    result.style.display = 'block';
    result.innerHTML = `
      <a href="${data.url}" target="_blank"
         style="display:flex;align-items:center;gap:12px;padding:12px;
                border-radius:10px;background:var(--p-elevated);
                border:1px solid var(--p-border);text-decoration:none;
                transition:border-color .15s"
         onmouseover="this.style.borderColor='var(--p-border2)'"
         onmouseout="this.style.borderColor='var(--p-border)'">
        ${data.image
          ? `<img src="${data.image}" style="${imgStyle}">`
          : `<div style="${imgStyle};display:flex;align-items:center;justify-content:center">
               <i class="bi bi-${isShop ? 'shop' : 'book'}"
                  style="font-size:18px;color:var(--p-hint)"></i>
             </div>`
        }
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;color:var(--p-text);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            ${data.name}
          </div>
          ${data.author
            ? `<div style="font-size:11px;color:var(--p-hint)">${data.author}</div>`
            : ''}
          ${isShop && data.rating
            ? `<div style="font-size:11px;color:var(--p-warning)">⭐ ${data.rating}</div>`
            : ''}
          ${!isShop && data.price
            ? `<div style="font-size:12px;color:var(--p-success);font-family:'DM Mono',monospace;font-weight:600">
                 ${Number(data.price).toLocaleString()} UZS
               </div>`
            : ''}
        </div>
        <div style="font-size:11px;color:var(--p-accent)">
          <i class="bi bi-arrow-up-right-square"></i>
        </div>
      </a>`;

  } catch(e) {
    result.style.display = 'block';
    result.innerHTML = `<div style="color:var(--p-danger);font-size:12px">
      <i class="bi bi-x-circle me-1"></i>Xatolik yuz berdi</div>`;
  } finally {
    btn.innerHTML = '<i class="bi bi-search"></i> Tekshirish';
    btn.disabled  = false;
  }
}

// Enter bilan preview trigger
document.getElementById('actionIdInput')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') { e.preventDefault(); previewAction(); }
});

// ── Rasm preview ──────────────────────────────────────
function showImgPreview(input) {
  if (!input.files[0]) return;
  const reader  = new FileReader();
  const prev    = document.getElementById('imgPreview');
  const prevEl  = document.getElementById('imgPreviewEl');
  const name    = document.getElementById('imgName');
  const zone    = document.getElementById('dropZone');

  reader.onload = e => {
    prevEl.src = e.target.result;
    prev.style.display = 'block';
    name.textContent   = input.files[0].name;
    name.style.display = 'block';
    zone.style.borderColor = 'var(--p-accent)';
  };
  reader.readAsDataURL(input.files[0]);
}

function handleImgDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.borderColor = 'var(--p-border)';
  const files = e.dataTransfer?.files;
  if (!files?.length) return;
  const input  = document.getElementById('imgInput');
  const dt     = new DataTransfer();
  dt.items.add(files[0]);
  input.files  = dt.files;
  showImgPreview(input);
}
</script>
<?php $__env->stopPush(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/market-news/_form.blade.php ENDPATH**/ ?>