<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Office CMS') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('loader.css') }}">
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div id="loading-bg">
            <div class="loading-logo">
                <svg width="86" height="46" viewBox="0 0 268 150" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 195.571 0)" fill="var(--initial-loader-color)" />
                    <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 196.084 0)" fill="url(#paint0)" fill-opacity=".4" />
                    <rect width="50.289" height="143.953" rx="25.144" transform="rotate(30.094 86.573 322.042) skewX(.187)" fill="var(--initial-loader-color)" />
                    <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 94.197 0)" fill="var(--initial-loader-color)" />
                    <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 94.197 0)" fill="url(#paint1)" fill-opacity=".4" />
                    <rect width="50.289" height="143.953" rx="25.144" transform="rotate(30.094 35.886 133.493) skewX(.187)" fill="var(--initial-loader-color)" />
                    <defs>
                        <linearGradient id="paint0" x1="25.144" y1="0" x2="25.144" y2="143.953" gradientUnits="userSpaceOnUse">
                            <stop />
                            <stop offset="1" stop-opacity="0" />
                        </linearGradient>
                        <linearGradient id="paint1" x1="25.144" y1="0" x2="25.144" y2="143.953" gradientUnits="userSpaceOnUse">
                            <stop />
                            <stop offset="1" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="loading">
                <div class="effect-1 effects"></div>
                <div class="effect-2 effects"></div>
                <div class="effect-3 effects"></div>
            </div>
        </div>
    </div>

    <script>
        const loaderBg = localStorage.getItem('Office CMS-initial-loader-bg') || '#FFFFFF';
        const loaderColor = localStorage.getItem('Office CMS-initial-loader-color') || '#666CFF';

        document.documentElement.style.setProperty('--initial-loader-bg', loaderBg);
        document.documentElement.style.setProperty('--initial-loader-color', loaderColor);
    </script>
</body>
</html>
