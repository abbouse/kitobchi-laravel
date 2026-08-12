@php
    $canonical = $canonical ?? url()->current();
    $ogType = $ogType ?? 'website';
    $robots = $robots ?? 'index, follow';
    $site = config('app.name', 'Kitobchi');
    $desc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($description))), 158, '…');
    $ogTitle = \Illuminate\Support\Str::limit($title, 88, '…');
    $ogImage = $ogImage ?? config('seo.og_image') ?: url('/images/logo/logo_blue.png');
    if ($ogImage !== '' && ! str_starts_with($ogImage, 'http')) {
        $ogImage = url($ogImage);
    }
@endphp
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ e($robots) }}, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="{{ e($robots) }}, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta name="description" content="{{ e($desc) }}">
<meta name="author" content="{{ e($site) }}">
<meta property="og:site_name" content="{{ e($site) }}">
<meta property="og:type" content="{{ e($ogType) }}">
<meta property="og:title" content="{{ e($ogTitle) }}">
<meta property="og:description" content="{{ e($desc) }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="uz_UZ">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ e($ogTitle) }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ e(\Illuminate\Support\Str::limit($title, 70, '…')) }}">
<meta name="twitter:description" content="{{ e($desc) }}">
<meta name="twitter:image" content="{{ $ogImage }}">

@if(isset($productPrice))
    <meta property="product:price:amount" content="{{ $productPrice }}">
    <meta property="product:price:currency" content="UZS">
@endif
@if(isset($productAvailability))
    <meta property="product:availability" content="{{ $productAvailability }}">
@endif
@if(!empty($articleModified))
    <meta property="article:modified_time" content="{{ $articleModified }}">
@endif
