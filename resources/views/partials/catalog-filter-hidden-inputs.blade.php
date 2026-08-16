{{-- Har bir katalog filtr popover'i (Narx/Do'konlar/Nashriyotlar/Muallif/
     Muqova/Material) alohida <form> — boshqa filtrlar submit paytida
     o'chib qolmasligi uchun ularni hidden input sifatida qayta chiqarish
     kerak. Bu partial shu takrorlanuvchi qismni bitta joyga jamlaydi.
     `except` — joriy formaning o'z (named) inputlari bilan
     to'qnashmasligi uchun shu filtrni o'tkazib yuborish. --}}
@php $except = $except ?? []; @endphp
<input type="hidden" name="type" value="{{ $type }}">
@if(!in_array('search', $except) && ($search ?? null))<input type="hidden" name="search" value="{{ $search }}">@endif
@if(!in_array('category', $except) && request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
@if(!in_array('sort', $except) && ($sort ?? 'popular') !== 'popular')<input type="hidden" name="sort" value="{{ $sort }}">@endif
@if(!in_array('price', $except))
    @if($priceMin ?? null)<input type="hidden" name="price_min" value="{{ $priceMin }}">@endif
    @if($priceMax ?? null)<input type="hidden" name="price_max" value="{{ $priceMax }}">@endif
@endif
@if(!in_array('sellers', $except))
    @foreach($selectedSellers ?? [] as $sid)<input type="hidden" name="seller_ids[]" value="{{ $sid }}">@endforeach
@endif
@if(!in_array('publishers', $except))
    @foreach($selectedPublishers ?? [] as $pid)<input type="hidden" name="publisher_ids[]" value="{{ $pid }}">@endforeach
@endif
@if(!in_array('authors', $except))
    @foreach($selectedAuthors ?? [] as $a)<input type="hidden" name="authors[]" value="{{ $a }}">@endforeach
@endif
@if(!in_array('cover_types', $except))
    @foreach($selectedCoverTypes ?? [] as $ct)<input type="hidden" name="cover_types[]" value="{{ $ct }}">@endforeach
@endif
@if(!in_array('materials', $except))
    @foreach($selectedMaterials ?? [] as $m)<input type="hidden" name="materials[]" value="{{ $m }}">@endforeach
@endif
