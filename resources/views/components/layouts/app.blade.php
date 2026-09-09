<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#3d47b8">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <title>{{ $title ?? 'E-Supervisi' }} — {{ config('app.name') }}</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                var dark = t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased">
    <div class="flex min-h-full"
         x-data="{
            theme: (() => { try { return localStorage.getItem('theme') || 'system' } catch (e) { return 'system' } })(),
            apply() {
                let dark = this.theme === 'dark' || (this.theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            },
            setTheme(v) {
                this.theme = v;
                try { v === 'system' ? localStorage.removeItem('theme') : localStorage.setItem('theme', v) } catch (e) {}
                this.apply();
            }
         }">
        <x-app.sidebar />

        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
            <x-app.topbar :title="$title ?? null" />

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    @if (session('status'))
                        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <div x-data="{ items: [] }"
         @notify.window="items.push({ id: Date.now(), message: $event.detail.message }); setTimeout(() => items.shift(), 4000)"
         class="fixed bottom-4 right-4 z-50 space-y-2">
        <template x-for="item in items" :key="item.id">
            <div class="rounded-md bg-ink-900 px-4 py-2 text-sm text-white shadow-lg" x-text="item.message"></div>
        </template>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>
