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
<body class="min-h-screen font-mono bg-term-bg text-term-text {{ (!isset($showNav) || $showNav) ? 'flex' : '' }}">
    @if(!isset($showNav) || $showNav)
        @include('partials.sidebar')
        <main class="app-main min-h-screen min-w-0 flex-1 overflow-auto">
            @yield('content')
        </main>
    @else
        <main>
            @yield('content')
        </main>
    @endif
    <script>
    document.addEventListener('click', function(e) {
        var close = e.target.closest('[data-modal-close]');
        if (close) {
            var backdrop = close.closest('.modal-backdrop');
            if (backdrop) backdrop.classList.add('hidden');
            return;
        }
        var open = e.target.closest('[data-modal]');
        if (open && open.dataset.modal) {
            var el = document.getElementById(open.dataset.modal);
            if (el) {
                el.classList.remove('hidden');
                if (open.dataset.modalSubmit) el.dataset.submit = open.dataset.modalSubmit;
            }
            return;
        }
        var confirmBtn = e.target.closest('.modal-confirm');
        if (confirmBtn) {
            var backdrop = confirmBtn.closest('.modal-backdrop');
            if (backdrop) {
                if (backdrop.dataset.submit) {
                    var form = document.getElementById(backdrop.dataset.submit);
                    if (form) form.submit();
                }
                backdrop.classList.add('hidden');
            }
        }
    });
    </script>
</body>
</html>
