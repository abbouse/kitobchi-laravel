@php
    $canonical = $canonical ?? url()->current();
    $ogType = $ogType ?? 'website';
    $robots = $robots ?? 'index, follow';
    $site = config('app.name', 'Kitobchi');
    $desc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($description ?? 'Kitobchi — online kitoblar va kanselyariya marketpleysi.'))), 158, '…');
    $ogTitle = \Illuminate\Support\Str::limit($title ?? 'Kitobchi', 88, '…');
    $ogImage = $ogImage ?? config('seo.og_image') ?: url('/og-image.png');
    if ($ogImage !== '' && ! str_starts_with($ogImage, 'http')) {
        $ogImage = url($ogImage);
    }
@endphp
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ e($robots) }}, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="{{ e($robots) }}, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta name="description" content="{{ e($desc) }}">
<meta name="author" content="{{ e($site) }}">

<!-- Open Graph / Facebook -->
<meta property="og:site_name" content="{{ e($site) }}">
<meta property="og:type" content="{{ e($ogType) }}">
<meta property="og:title" content="{{ e($ogTitle) }}">
<meta property="og:description" content="{{ e($desc) }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="uz_UZ">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ e($ogTitle) }}">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ e(\Illuminate\Support\Str::limit($title ?? 'Kitobchi', 70, '…')) }}">
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

<!-- Schema.org JSON-LD Structured Data (Google Rich Snippets) -->
@if($ogType === 'product' && isset($productPrice))
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": @json(!empty($isbn) || !empty($author) ? ["Product", "Book"] : "Product"),
  "name": @json($title),
  "image": [
    @json($ogImage)
    @if(!empty($extraImages))
      @foreach($extraImages as $eImg)
        , @json(str_starts_with($eImg, 'http') ? $eImg : url($eImg))
      @endforeach
    @endif
  ],
  "description": @json($desc),
  @if(!empty($sku))
  "sku": @json($sku),
  @endif
  @if(!empty($isbn))
  "isbn": @json($isbn),
  @endif
  @if(!empty($author) && $author !== 'null')
  "author": {
    "@type": "Person",
    "name": @json($author)
  },
  @endif
  @if(!empty($publisher))
  "publisher": {
    "@type": "Organization",
    "name": @json($publisher)
  },
  @endif
  "brand": {
    "@type": "Brand",
    "name": @json($brand ?? 'Kitobchi')
  },
  @if(!empty($sku))
  "mpn": @json($sku),
  @endif
  @if(!empty($isbn))
  "gtin13": @json(preg_replace('/[^0-9]/', '', $isbn)),
  @endif
  "offers": {
    "@type": "Offer",
    "url": @json($canonical),
    "priceCurrency": "UZS",
    "price": @json((int) $productPrice),
    "priceValidUntil": @json(date('Y-12-31')),
    "itemCondition": "https://schema.org/NewCondition",
    "availability": @json(($productAvailability ?? 'in stock') === 'in stock' ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"),
    "seller": {
      "@type": "Organization",
      "name": @json($brand ?? 'Kitobchi')
    },
    "hasMerchantReturnPolicy": {
      "@type": "MerchantReturnPolicy",
      "applicableCountry": "UZ",
      "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
      "merchantReturnDays": 4,
      "returnMethod": "https://schema.org/ReturnByMail",
      "returnFees": "https://schema.org/FreeReturn"
    },
    "shippingDetails": {
      "@type": "OfferShippingDetails",
      "shippingRate": {
        "@type": "MonetaryAmount",
        "value": 15000,
        "currency": "UZS"
      },
      "shippingDestination": {
        "@type": "DefinedRegion",
        "addressCountry": "UZ"
      },
      "deliveryTime": {
        "@type": "ShippingDeliveryTime",
        "handlingTime": {
          "@type": "QuantitativeValue",
          "minValue": 0,
          "maxValue": 1,
          "unitCode": "DAY"
        },
        "transitTime": {
          "@type": "QuantitativeValue",
          "minValue": 1,
          "maxValue": 3,
          "unitCode": "DAY"
        }
      }
    }
  }
  @if(!empty($ratingValue) && (float)$ratingValue > 0)
  ,
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": @json((string) number_format((float) $ratingValue, 1)),
    "reviewCount": @json(max(1, (int) ($reviewCount ?? 1))),
    "bestRating": "5",
    "worstRating": "1"
  }
  @endif
}
</script>
@endif

@if(!empty($breadcrumbs) && is_array($breadcrumbs))
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    @foreach($breadcrumbs as $bIndex => $bItem)
    {
      "@type": "ListItem",
      "position": {{ $bIndex + 1 }},
      "name": @json($bItem['name'] ?? ''),
      "item": @json($bItem['url'] ?? '')
    }{{ $loop->last ? '' : ',' }}
    @endforeach
  ]
}
</script>
@endif

@if($ogType === 'website')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Kitobchi",
  "url": @json(url('/')),
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": @json(url('/catalog') . '?search={search_term_string}')
    },
    "query-input": "required name=search_term_string"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Kitobchi",
    "url": @json(url('/')),
    "logo": {
      "@type": "ImageObject",
      "url": @json(url('/images/logo/logo_blue.png'))
    }
  }
}
</script>
@endif
