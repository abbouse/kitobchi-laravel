<?php $__env->startSection('title', 'Siyosatlar'); ?>

<?php $__env->startPush('styles'); ?>
<style>
.policy-row { transition: background .15s; }
.policy-row:hover { background: var(--p-hover); }
.policy-drag-handle {
  cursor: grab;
  color: var(--p-hint);
  padding: 4px 6px;
  border-radius: 4px;
  transition: color .15s, background .15s;
}
.policy-drag-handle:hover { color: var(--p-muted); background: var(--p-elevated); }
.content-preview {
  font-size: 12px;
  color: var(--p-hint);
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
/* Editor toolbar */
.editor-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 10px 12px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  border-bottom: none;
  border-radius: 10px 10px 0 0;
}
.editor-toolbar button {
  padding: 5px 10px;
  border: 1px solid var(--p-border);
  border-radius: 6px;
  background: var(--p-surface);
  color: var(--p-muted);
  font-size: 12px;
  cursor: pointer;
  transition: all .15s;
  display: flex;
  align-items: center;
  gap: 4px;
}
.editor-toolbar button:hover {
  background: var(--p-hover);
  color: var(--p-text);
  border-color: var(--p-border2);
}
.editor-toolbar .sep {
  width: 1px;
  background: var(--p-border);
  margin: 2px 4px;
  align-self: stretch;
}
.rich-editor {
  min-height: 320px;
  padding: 16px;
  background: var(--p-surface);
  border: 1px solid var(--p-border);
  border-radius: 0 0 10px 10px;
  color: var(--p-text);
  outline: none;
  font-size: 14px;
  line-height: 1.7;
  overflow-y: auto;
}
.rich-editor:focus { border-color: var(--p-accent); }
.rich-editor h1,.rich-editor h2,.rich-editor h3 { color: var(--p-text); margin: .75em 0 .35em; font-weight: 600; }
.rich-editor h1 { font-size: 1.5em; }
.rich-editor h2 { font-size: 1.25em; }
.rich-editor h3 { font-size: 1.1em; }
.rich-editor p { margin: 0 0 .75em; }
.rich-editor ul,.rich-editor ol { padding-left: 1.4em; margin-bottom: .75em; }
.rich-editor li { margin-bottom: .25em; }
.rich-editor strong { color: var(--p-text); font-weight: 600; }
.rich-editor a { color: var(--p-accent); }
.rich-editor blockquote {
  border-left: 3px solid var(--p-accent);
  margin: .75em 0;
  padding: .5em 1em;
  background: var(--p-accent-d);
  border-radius: 0 8px 8px 0;
  color: var(--p-muted);
}
.rich-editor hr { border: none; border-top: 1px solid var(--p-border); margin: 1em 0; }
.slug-input { font-family: 'JetBrains Mono', monospace; font-size: 13px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>


<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Siyosatlar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Ommaviy siyosat sahifalari — foydalanish shartlari, maxfiylik va boshqalar <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <button class="btn-p primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg"></i> Yangi siyosat
      </button>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>



<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  <?php $active = $policies->where('is_active', true)->count(); $total = $policies->count(); ?>
  <?php $__currentLoopData = [
    ['Jami',  $total,          'accent',  'bi-file-earmark-text'],
    ['Faol',  $active,         'success', 'bi-check-circle'],
    ['Nofaol',$total-$active,  'muted',   'bi-eye-slash'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="col-4">
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-<?php echo e($c); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($c); ?>,var(--p-muted));
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($i); ?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)"><?php echo e($v); ?></div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em"><?php echo e($l); ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="p-card fade-up d2">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th style="width:40px"></th>
          <th>#</th>
          <th>Sarlavha</th>
          <th>Slug (URL)</th>
          <th>Kontent</th>
          <th>Tartib</th>
          <th>Ilovada</th>
          <th>Status</th>
          <th>Yangilandi</th>
          <th style="width:110px"></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr class="policy-row">
          <td>
            <span class="policy-drag-handle" title="Tartibni o'zgartirish">
              <i class="bi bi-grip-vertical"></i>
            </span>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($policy->id); ?>

          </td>
          <td>
            <div style="font-weight:600;font-size:13px;color:var(--p-text)"><?php echo e($policy->title); ?></div>
          </td>
          <td>
            <code style="font-size:11px;background:var(--p-elevated);padding:2px 7px;border-radius:5px;color:var(--p-muted)">
              /legal/<?php echo e($policy->slug); ?>

            </code>
          </td>
          <td>
            <div class="content-preview"><?php echo e(strip_tags($policy->content)); ?></div>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--p-muted)">
            <?php echo e($policy->sort_order); ?>

          </td>
          <td>
            <?php if($policy->show_in_app): ?>
              <span class="s-pill success" style="font-size:10px"><i class="bi bi-check-lg"></i> Ha</span>
            <?php else: ?>
              <span class="s-pill muted" style="font-size:10px">Yo'q</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" action="<?php echo e(route('panel.policies.toggle', $policy)); ?>" style="display:inline">
              <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
              <button type="submit"
                      class="btn-p <?php echo e($policy->is_active ? 'success' : 'ghost'); ?> sm"
                      title="<?php echo e($policy->is_active ? 'Faol — o\'chirish' : 'Nofaol — yoqish'); ?>">
                <i class="bi <?php echo e($policy->is_active ? 'bi-toggle-on' : 'bi-toggle-off'); ?>"></i>
                <?php echo e($policy->is_active ? 'Faol' : 'Nofaol'); ?>

              </button>
            </form>
          </td>
          <td style="font-size:11px;color:var(--p-hint)">
            <?php echo e($policy->updated_at->format('d.m.Y')); ?>

          </td>
          <td>
            <div class="flex gap-1 justify-end">
              <a href="/legal/<?php echo e($policy->slug); ?>" target="_blank"
                 class="btn-p ghost sm" title="Ko'rish">
                <i class="bi bi-eye"></i>
              </a>
              <button class="btn-p ghost sm"
                      onclick="openEdit(<?php echo e($policy->id); ?>)"
                      title="Tahrirlash">
                <i class="bi bi-pencil"></i>
              </button>
              <form method="POST" action="<?php echo e(route('panel.policies.destroy', $policy)); ?>"
                    onsubmit="return confirm('Siyosatni o\'chirishga ishonchingiz komilmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn-p danger sm" title="O'chirish">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="10">
            <div class="p-empty-cell">
              <i class="bi bi-file-earmark-text" style="font-size:36px;display:block;margin-bottom:12px;opacity:.4"></i>
              <div style="font-size:14px">Hali siyosat qo'shilmagan</div>
              <div style="font-size:12px;margin-top:4px">Yuqoridagi «Yangi siyosat» tugmasini bosing</div>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>


<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form method="POST" action="<?php echo e(route('panel.policies.store')); ?>" id="createForm">
      <?php echo csrf_field(); ?>
      <div class="modal-content" style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px">
        <div class="modal-header" style="border-color:var(--p-border)">
          <h5 class="modal-title" style="font-size:15px;font-weight:700">
            <i class="bi bi-file-earmark-plus mr-2" style="color:var(--p-accent)"></i>
            Yangi siyosat
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="md:col-span-8">
              <label class="p-form-label">Sarlavha <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="title" class="p-form-control"
                     placeholder="Masalan: Maxfiylik siyosati"
                     oninput="autoSlug(this,'create')" required>
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">
                Slug (URL)
                <span style="font-size:10px;color:var(--p-hint);font-weight:400">— avtomatik</span>
              </label>
              <input type="text" name="slug" id="create-slug" class="p-form-control slug-input"
                     placeholder="maxfiylik-siyosati">
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">Tartib raqami</label>
              <input type="number" name="sort_order" class="p-form-control" value="0" min="0">
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">Ilovada ko'rsatish</label>
              <div class="mt-2">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--p-muted)">
                  <input type="hidden" name="show_in_app" value="0">
                  <input type="checkbox" name="show_in_app" value="1" checked
                         style="width:16px;height:16px;cursor:pointer;accent-color:var(--p-accent)">
                  Ilovada ko'rsatilsin
                </label>
              </div>
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">Holati</label>
              <div class="mt-2">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--p-muted)">
                  <input type="hidden" name="is_active" value="0">
                  <input type="checkbox" name="is_active" value="1" checked
                         style="width:16px;height:16px;cursor:pointer;accent-color:var(--p-success)">
                  Faol (ommaviy ko'rinadi)
                </label>
              </div>
            </div>
            <div class="">
              <label class="p-form-label">Kontent <span style="color:var(--p-danger)">*</span></label>
              <?php echo $__env->make('panel.policies.partials.editor', ['id' => 'create'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
              <input type="hidden" name="content" id="create-content">
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-color:var(--p-border)">
          <button type="button" class="btn-p ghost" data-bs-dismiss="modal">Bekor qilish</button>
          <button type="submit" class="btn-p primary" onclick="syncContent('create')">
            <i class="bi bi-floppy-fill mr-1"></i> Saqlash
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


<?php $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade" id="editModal-<?php echo e($policy->id); ?>" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form method="POST" action="<?php echo e(route('panel.policies.update', $policy)); ?>" id="editForm-<?php echo e($policy->id); ?>">
      <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
      <div class="modal-content" style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px">
        <div class="modal-header" style="border-color:var(--p-border)">
          <h5 class="modal-title" style="font-size:15px;font-weight:700">
            <i class="bi bi-pencil-square mr-2" style="color:var(--p-warning)"></i>
            Tahrirlash: <?php echo e($policy->title); ?>

          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="md:col-span-8">
              <label class="p-form-label">Sarlavha <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="title" class="p-form-control"
                     value="<?php echo e($policy->title); ?>"
                     oninput="autoSlug(this,'edit-<?php echo e($policy->id); ?>')" required>
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">Slug (URL)</label>
              <input type="text" name="slug" id="edit-<?php echo e($policy->id); ?>-slug"
                     class="p-form-control slug-input" value="<?php echo e($policy->slug); ?>">
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">Tartib raqami</label>
              <input type="number" name="sort_order" class="p-form-control"
                     value="<?php echo e($policy->sort_order); ?>" min="0">
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">Ilovada ko'rsatish</label>
              <div class="mt-2">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--p-muted)">
                  <input type="hidden" name="show_in_app" value="0">
                  <input type="checkbox" name="show_in_app" value="1"
                         <?php echo e($policy->show_in_app ? 'checked' : ''); ?>

                         style="width:16px;height:16px;cursor:pointer;accent-color:var(--p-accent)">
                  Ilovada ko'rsatilsin
                </label>
              </div>
            </div>
            <div class="md:col-span-4">
              <label class="p-form-label">Holati</label>
              <div class="mt-2">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--p-muted)">
                  <input type="hidden" name="is_active" value="0">
                  <input type="checkbox" name="is_active" value="1"
                         <?php echo e($policy->is_active ? 'checked' : ''); ?>

                         style="width:16px;height:16px;cursor:pointer;accent-color:var(--p-success)">
                  Faol (ommaviy ko'rinadi)
                </label>
              </div>
            </div>
            <div class="">
              <label class="p-form-label">Kontent <span style="color:var(--p-danger)">*</span></label>
              <?php echo $__env->make('panel.policies.partials.editor', ['id' => 'edit-'.$policy->id, 'content' => $policy->content], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
              <input type="hidden" name="content" id="edit-<?php echo e($policy->id); ?>-content">
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-color:var(--p-border)">
          <button type="button" class="btn-p ghost" data-bs-dismiss="modal">Bekor qilish</button>
          <button type="submit" class="btn-p primary" onclick="syncContent('edit-<?php echo e($policy->id); ?>')">
            <i class="bi bi-floppy-fill mr-1"></i> Saqlash
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function autoSlug(input, prefix) {
  const slugInput = document.getElementById(prefix + '-slug');
  if (!slugInput || slugInput.dataset.manual) return;
  slugInput.value = input.value
    .toLowerCase()
    .replace(/[^a-z0-9\s\-а-яёўқғҳ]/gi, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}

document.querySelectorAll('.slug-input').forEach(el => {
  el.addEventListener('input', () => { el.dataset.manual = '1'; });
});

function syncContent(prefix) {
  const editor = document.getElementById(prefix + '-editor');
  const hidden = document.getElementById(prefix + '-content');
  if (editor && hidden) hidden.value = editor.innerHTML;
}

function openEdit(id) {
  const modal = new bootstrap.Modal(document.getElementById('editModal-' + id));
  modal.show();
}

function execCmd(cmd, value, prefix) {
  const editor = document.getElementById(prefix + '-editor');
  editor.focus();
  document.execCommand(cmd, false, value || null);
}

function insertHTML(html, prefix) {
  const editor = document.getElementById(prefix + '-editor');
  editor.focus();
  document.execCommand('insertHTML', false, html);
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/policies/index.blade.php ENDPATH**/ ?>