<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#24356e">
    <title>{{ $title ?? 'Masuk' }} — {{ config('app.name') }}</title>
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
    <div class="flex min-h-full">
        {{-- Panel brand (desktop) --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-brand-900 p-12 text-brand-50 lg:flex xl:w-[55%]">
            <div class="absolute inset-0 opacity-[0.07]"
                 style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;"></div>
            <div class="absolute -right-24 -top-24 size-96 rounded-full bg-brand-700/40 blur-3xl"></div>

            <a href="{{ url('/') }}" class="relative flex items-center gap-2.5">
                <span class="flex size-9 items-center justify-center rounded-lg bg-white/10">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                        <path d="M12 3 4 7v6c0 4.5 3.4 7.3 8 8 4.6-.7 8-3.5 8-8V7l-8-4Z" stroke-linejoin="round"/>
                        <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="text-base font-bold tracking-tight">E-Supervisi Klinis Pendidikan</span>
            </a>

            <div class="relative max-w-md">
                <h2 class="text-3xl font-bold leading-tight tracking-tight">Satu platform untuk keenam tahap siklus supervisi klinis.</h2>
                <p class="mt-4 text-sm leading-relaxed text-brand-100">
                    Perencanaan → Observasi → Analisis → Umpan Balik → Tindak Lanjut → Pelaporan Berbasis Data.
                    Dirancang tangguh untuk kondisi jaringan terbatas.
                </p>
                <ol class="mt-8 space-y-2.5 text-sm">
                    @foreach (['Perencanaan', 'Observasi Pembelajaran', 'Analisis Hasil', 'Umpan Balik', 'Tindak Lanjut', 'Pelaporan'] as $i => $step)
                        <li class="flex items-center gap-3">
                            <span class="flex size-6 items-center justify-center rounded-full bg-white/10 text-xs font-bold">{{ $i + 1 }}</span>
                            <span class="text-brand-50">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            <p class="relative text-xs text-brand-200">Evidence-informed · Human-in-the-loop · Kepatuhan UU 27/2022 PDP</p>
        </div>

        {{-- Panel form --}}
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:px-16 xl:px-24">
            <div class="mx-auto w-full max-w-sm">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-brand-800 dark:text-brand-300 lg:hidden">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path d="M12 3 4 7v6c0 4.5 3.4 7.3 8 8 4.6-.7 8-3.5 8-8V7l-8-4Z" stroke-linejoin="round"/>
                        <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-base font-bold tracking-tight">E-Supervisi</span>
                </a>

                <h1 class="mt-8 text-2xl font-bold tracking-tight text-ink-900 dark:text-ink-50 lg:mt-0">
                    {{ $heading ?? 'Masuk ke akun Anda' }}
                </h1>
                <p class="mt-1.5 text-sm text-[var(--text-muted)]">Gunakan kredensial dari Admin Sekolah / Dinas Anda.</p>

                @if (session('status'))
                    <div class="mt-6 rounded-lg bg-status-done/10 px-3.5 py-2.5 text-sm text-status-done ring-1 ring-inset ring-status-done/20" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mt-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
