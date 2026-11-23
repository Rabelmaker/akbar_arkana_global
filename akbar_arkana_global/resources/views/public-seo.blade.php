@php
    $brand = config('company.brandName');
    $site = config('company.website');
    $contact = request()->routeIs('contact');
    $title = $contact ? "Start a Project | $brand" : "$brand | Technology Solutions & International Trade";
    $description = $contact
        ? 'Contact PT Akbar Arkana Global in Bekasi to discuss a website, SaaS platform, IoT project, or Indonesian spice export inquiry.'
        : 'PT Akbar Arkana Global develops websites, SaaS platforms, and IoT solutions in Indonesia and connects international buyers with Indonesian spices through Akbar Spices International.';
    $url = $site . ($contact ? '/contact' : '/');
    $logo = $site . '/assets/branding/aag-icon-512.png';
    $organization = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => config('company.name'), 'alternateName' => $brand, 'url' => $site, 'logo' => $logo, 'email' => config('company.email')];
@endphp
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $url }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $brand }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:image" content="{{ $logo }}">
<meta property="og:image:alt" content="{{ $brand }}">
<meta name="twitter:card" content="summary">
@if (!$contact)
    <script type="application/ld+json">{!! json_encode($organization, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
