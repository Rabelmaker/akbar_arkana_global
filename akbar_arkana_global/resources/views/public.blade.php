<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @if (request()->routeIs('home', 'contact'))
            @include('public-seo')
        @else
            <title>Page not found | {{ config('company.brandName') }}</title>
            <meta name="robots" content="noindex">
        @endif
        @include('brand-icons')
        @fonts
        @vite('resources/js/public/main.ts')
    </head>
    <body>
        <div id="aag-app"></div>
        <noscript>
            <h1>{{ config('company.name') }}</h1>
            <p>Technology solutions and international trade.</p>
            <a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a>
        </noscript>
    </body>
</html>
