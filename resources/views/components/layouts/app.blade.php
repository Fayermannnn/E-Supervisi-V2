<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#24356e">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <title>{{ $title ?? 'E-Supervisi' }} — {{ config('app.name') }}</title>
    @fonts
    {{-- Boot tema anti-FOUC — statis & byte-stable; di-whitelist via hash CSP (config/security.php). --}}
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

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto max-w-6xl space-y-6">
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
            <div x-transition.opacity.duration.200ms
                 class="flex items-center gap-2.5 rounded-lg bg-ink-900 py-2.5 pl-3 pr-4 text-sm font-medium text-white shadow-lg dark:bg-ink-50 dark:text-ink-900">
                <span class="flex size-4 items-center justify-center rounded-full bg-status-done text-[10px] font-bold text-white">✓</span>
                <span x-text="item.message"></span>
            </div>
        </template>
    </div>

    {{-- Confetti: kejutan kecil saat momen selesai (laporan siklus disusun — akhir
         enam tahap). Murni CSS/Alpine — tanpa dependency baru, tanpa <script> inline
         (jadi tak butuh nonce/hash CSP). Lihat @keyframes confetti-fall di app.css. --}}
    <div x-data="{ pieces: [] }"
         @celebrate.window="
            pieces = Array.from({ length: 70 }, (_, i) => ({
                id: Date.now() + i,
                x: Math.random() * 100,
                delay: (Math.random() * 0.5).toFixed(2),
                dur: (2 + Math.random() * 1.4).toFixed(2),
                rot: Math.floor(Math.random() * 360),
                w: 6 + Math.round(Math.random() * 4),
                color: ['#3450d3', '#15803d', '#b45309', '#b91c1c', '#24356e', '#eab308'][i % 6],
            }));
            setTimeout(() => pieces = [], 3800)
         "
         class="pointer-events-none fixed inset-0 z-[70] overflow-hidden" aria-hidden="true">
        <template x-for="p in pieces" :key="p.id">
            <span class="absolute -top-3 block rounded-[1px]"
                  :style="`left:${p.x}%; width:${p.w}px; height:${p.w * 2.4}px; background:${p.color}; animation: confetti-fall ${p.dur}s ${p.delay}s ease-in forwards; transform: rotate(${p.rot}deg)`"></span>
        </template>
    </div>

    {{-- Statis & byte-stable; di-whitelist via hash CSP. data-navigate-once: jangan jalankan ulang saat wire:navigate. --}}
    <script data-navigate-once>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>
