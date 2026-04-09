{{-- resources/views/panel/chats/_user-cell.blade.php --}}
<div class="d-flex align-items-center gap-2">
  <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
              background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
              display:flex;align-items:center;justify-content:center;
              font-size:11px;font-weight:700;color:#fff">
    @if($avatar ?? null)
      <img src="{{ $avatar }}" style="width:100%;height:100%;object-fit:cover">
    @else
      {{ strtoupper(substr($name??'U',0,1)) }}
    @endif
  </div>
  <div>
    @if($id ?? null)
    <a href="{{ route('panel.users.show',$id) }}"
       style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
      {{ $name }} {{ $lname }}
    </a>
    @else
    <div style="font-size:12.5px;color:var(--p-text)">{{ $name }} {{ $lname }}</div>
    @endif
  </div>
</div>