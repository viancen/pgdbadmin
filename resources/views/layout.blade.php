<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ isset($title) ? $title . ' — ' : '' }}PG Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-mono bg-term-bg text-term-text">
    @if(!isset($showNav) || $showNav)
        @include('partials.nav')
    @endif
    <main class="{{ (!isset($showNav) || $showNav) ? 'pt-12' : '' }}">
        @yield('content')
    </main>
</body>
</html>
