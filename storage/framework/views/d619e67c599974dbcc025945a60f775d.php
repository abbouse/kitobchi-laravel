<?php $editorContent = $content ?? ''; ?>

<div class="editor-toolbar">
  <button type="button" onclick="execCmd('bold',null,'<?php echo e($id); ?>')" title="Bold"><i class="bi bi-type-bold"></i></button>
  <button type="button" onclick="execCmd('italic',null,'<?php echo e($id); ?>')" title="Italic"><i class="bi bi-type-italic"></i></button>
  <button type="button" onclick="execCmd('underline',null,'<?php echo e($id); ?>')" title="Underline"><i class="bi bi-type-underline"></i></button>
  <button type="button" onclick="execCmd('strikeThrough',null,'<?php echo e($id); ?>')" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="execCmd('formatBlock',null,'<?php echo e($id); ?>'); document.execCommand('formatBlock',false,'h1')" title="H1"
          onclick="execCmd('formatBlock','h1','<?php echo e($id); ?>')"><b style="font-size:11px">H1</b></button>
  <button type="button" onclick="execCmd('formatBlock','h2','<?php echo e($id); ?>')" title="H2"><b style="font-size:11px">H2</b></button>
  <button type="button" onclick="execCmd('formatBlock','h3','<?php echo e($id); ?>')" title="H3"><b style="font-size:11px">H3</b></button>
  <button type="button" onclick="execCmd('formatBlock','p','<?php echo e($id); ?>')" title="Paragraf"><i class="bi bi-paragraph"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="execCmd('insertUnorderedList',null,'<?php echo e($id); ?>')" title="Ro'yxat"><i class="bi bi-list-ul"></i></button>
  <button type="button" onclick="execCmd('insertOrderedList',null,'<?php echo e($id); ?>')" title="Raqamli ro'yxat"><i class="bi bi-list-ol"></i></button>
  <button type="button" onclick="insertHTML('<blockquote>Iqtibos</blockquote>','<?php echo e($id); ?>')" title="Iqtibos"><i class="bi bi-quote"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="execCmd('justifyLeft',null,'<?php echo e($id); ?>')" title="Chapga"><i class="bi bi-text-left"></i></button>
  <button type="button" onclick="execCmd('justifyCenter',null,'<?php echo e($id); ?>')" title="Markazga"><i class="bi bi-text-center"></i></button>
  <button type="button" onclick="execCmd('justifyRight',null,'<?php echo e($id); ?>')" title="O'ngga"><i class="bi bi-text-right"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="insertHTML('<hr/>','<?php echo e($id); ?>')" title="Gorizontal chiziq"><i class="bi bi-dash-lg"></i></button>
  <button type="button" onclick="execCmd('removeFormat',null,'<?php echo e($id); ?>')" title="Formatlashni tozalash"
          style="color:var(--p-danger)"><i class="bi bi-eraser"></i></button>
</div>
<div class="rich-editor"
     id="<?php echo e($id); ?>-editor"
     contenteditable="true"
     oninput="(function(el){const h=document.getElementById('<?php echo e($id); ?>-content');if(h)h.value=el.innerHTML;})(this)"
><?php echo $editorContent; ?></div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/policies/partials/editor.blade.php ENDPATH**/ ?>