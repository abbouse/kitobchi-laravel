@php $editorContent = $content ?? ''; @endphp

<div class="editor-toolbar">
  <button type="button" onclick="execCmd('bold',null,'{{ $id }}')" title="Bold"><i class="bi bi-type-bold"></i></button>
  <button type="button" onclick="execCmd('italic',null,'{{ $id }}')" title="Italic"><i class="bi bi-type-italic"></i></button>
  <button type="button" onclick="execCmd('underline',null,'{{ $id }}')" title="Underline"><i class="bi bi-type-underline"></i></button>
  <button type="button" onclick="execCmd('strikeThrough',null,'{{ $id }}')" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="execCmd('formatBlock','h1','{{ $id }}')" title="H1"><b style="font-size:11px">H1</b></button>
  <button type="button" onclick="execCmd('formatBlock','h2','{{ $id }}')" title="H2"><b style="font-size:11px">H2</b></button>
  <button type="button" onclick="execCmd('formatBlock','h3','{{ $id }}')" title="H3"><b style="font-size:11px">H3</b></button>
  <button type="button" onclick="execCmd('formatBlock','p','{{ $id }}')" title="Paragraf"><i class="bi bi-paragraph"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="execCmd('insertUnorderedList',null,'{{ $id }}')" title="Ro'yxat"><i class="bi bi-list-ul"></i></button>
  <button type="button" onclick="execCmd('insertOrderedList',null,'{{ $id }}')" title="Raqamli ro'yxat"><i class="bi bi-list-ol"></i></button>
  <button type="button" onclick="insertHTML('<blockquote>Iqtibos</blockquote>','{{ $id }}')" title="Iqtibos"><i class="bi bi-quote"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="execCmd('justifyLeft',null,'{{ $id }}')" title="Chapga"><i class="bi bi-text-left"></i></button>
  <button type="button" onclick="execCmd('justifyCenter',null,'{{ $id }}')" title="Markazga"><i class="bi bi-text-center"></i></button>
  <button type="button" onclick="execCmd('justifyRight',null,'{{ $id }}')" title="O'ngga"><i class="bi bi-text-right"></i></button>
  <div class="sep"></div>
  <button type="button" onclick="insertHTML('<hr/>','{{ $id }}')" title="Gorizontal chiziq"><i class="bi bi-dash-lg"></i></button>
  <button type="button" onclick="execCmd('removeFormat',null,'{{ $id }}')" title="Formatlashni tozalash"
          style="color:var(--p-danger)"><i class="bi bi-eraser"></i></button>
</div>
<div class="rich-editor"
     id="{{ $id }}-editor"
     contenteditable="true"
     oninput="(function(el){const h=document.getElementById('{{ $id }}-content');if(h)h.value=el.innerHTML;})(this)"
>{!! $editorContent !!}</div>
