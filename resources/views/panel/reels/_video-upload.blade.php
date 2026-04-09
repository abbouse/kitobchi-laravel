{{-- resources/views/panel/reels/_video-upload.blade.php --}}
{{-- $field, $label, $color, $required, $maxMb --}}

<div>
  <label class="p-form-label">
    <span class="s-pill {{ $color }}" style="font-size:10px;margin-right:4px">
      {{ $label }}
    </span>
    @if($required)
      <span style="color:var(--p-danger)">*</span>
    @else
      <span style="color:var(--p-hint);font-size:10px">ixtiyoriy</span>
    @endif
  </label>

  <div id="drop-{{ $field }}"
       style="border:2px dashed var(--p-border);border-radius:10px;
              padding:16px 12px;text-align:center;cursor:pointer;
              transition:border-color .2s,background .2s;position:relative"
       onclick="document.getElementById('{{ $field }}').click()"
       ondragover="event.preventDefault();this.style.borderColor='var(--p-{{ $color }})'"
       ondragleave="this.style.borderColor='var(--p-border)'"
       ondrop="handleDrop(event,'{{ $field }}')">

    <div id="icon-{{ $field }}">
      <i class="bi bi-cloud-arrow-up"
         style="font-size:22px;color:var(--p-{{ $color }});display:block;margin-bottom:6px"></i>
      <div style="font-size:12px;color:var(--p-muted)">
        Fayl tanlang yoki tashlang
      </div>
      <div style="font-size:10px;color:var(--p-hint);margin-top:2px">
        MP4, WebM · Maks {{ $maxMb }}MB
      </div>
    </div>

    {{-- Tanlangan fayl nomi --}}
    <div id="name-{{ $field }}"
         style="display:none;font-size:11.5px;color:var(--p-text);
                font-weight:500;word-break:break-all"></div>
  </div>

  <input type="file" id="{{ $field }}" name="{{ $field }}"
         accept="video/mp4,video/webm,video/quicktime"
         style="display:none"
         {{ $required ? 'required' : '' }}
         onchange="showFileName('{{ $field }}')">

  @error($field)
    <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
  @enderror
</div>