<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#24356e">
    <meta name="description" content="Sistem E-Supervisi Klinis Pendidikan v2.0 — satu platform untuk keenam tahap siklus supervisi klinis, dari perencanaan hingga pelaporan berbasis data. Evidence-informed, tangguh-jaringan.">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <title>{{ config('app.name') }} — Supervisi Klinis Pendidikan</title>
    @fonts
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                var dark = t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-[var(--surface)] antialiased">

@php
    $tahap = [
        ['no' => 1, 'nama' => 'Perencanaan', 'tahap' => 'Pra-observasi', 'desc' => 'Kesepakatan fokus & jadwal observasi, pemilihan instrumen, refleksi pra-observasi guru.'],
        ['no' => 2, 'nama' => 'Observasi Pembelajaran', 'tahap' => 'Observasi', 'desc' => 'Pencatatan observasi terstruktur, mampu bekerja luring, unggah rekaman, pengisian instrumen.'],
        ['no' => 3, 'nama' => 'Analisis Hasil', 'tahap' => 'Pasca-observasi 3a', 'desc' => 'Skoring deterministik dari konfigurasi instrumen, identifikasi pola, draf analisis dibantu AI.'],
        ['no' => 4, 'nama' => 'Umpan Balik', 'tahap' => 'Pasca-observasi 3b', 'desc' => 'Ruang percakapan reflektif kolaboratif; guru mengonfirmasi penerimaan.'],
        ['no' => 5, 'nama' => 'Tindak Lanjut', 'tahap' => 'Pasca-observasi 3c', 'desc' => 'RTL terikat tenggat, pengingat otomatis, bukti pelaksanaan, eskalasi bila terlambat.'],
        ['no' => 6, 'nama' => 'Pelaporan Berbasis Data', 'tahap' => 'Pasca-observasi 3d', 'desc' => 'Laporan per siklus & agregat lintas sekolah, ekspor PDF/XLSX untuk pengambilan keputusan.'],
    ];

    $prinsip = [
        ['judul' => 'Evidence-first', 'desc' => 'Prioritas modul mengikuti gap riset (SLR), bukan asumsi kelengkapan fitur.'],
        ['judul' => 'Tangguh-jaringan', 'desc' => 'PWA dengan mode luring untuk observasi & bukti tindak lanjut — selaras kondisi wilayah 3T.'],
        ['judul' => 'Human-in-the-loop', 'desc' => 'AI menyusun draf; supervisor wajib meninjau. AI tidak pernah mengambil keputusan final.'],
        ['judul' => 'RBAC ketat', 'desc' => 'Guru hanya datanya; supervisor hanya binaannya; Admin Dinas hanya agregat. Default deny.'],
        ['judul' => 'Audit append-only', 'desc' => 'Jejak seluruh perubahan status & data sensitif — tidak dapat diubah atau dihapus.'],
        ['judul' => 'Kepatuhan UU 27/2022', 'desc' => 'Data guru diperlakukan sebagai data pribadi; retensi = arsip, bukan hapus.'],
    ];

    $aktor = [
        ['peran' => 'Guru', 'desc' => 'Subjek supervisi — mengisi refleksi, menerima umpan balik, melaksanakan tindak lanjut, menilai proses supervisi (360°).'],
        ['peran' => 'Supervisor', 'desc' => 'Kepala Sekolah / Pengawas — menjalankan keenam tahap siklus untuk guru binaannya.'],
        ['peran' => 'Admin Dinas', 'desc' => 'Pemantauan agregat lintas sekolah, kelola bank instrumen & katalog PKB, kurasi praktik baik.'],
        ['peran' => 'Admin Sistem', 'desc' => 'Manajemen pengguna, konfigurasi, audit log — tanpa akses konten pedagogis.'],
    ];
@endphp

{{-- ── Navbar ─────────────────────────────────────────────── --}}
<header class="sticky top-0 z-40 border-b border-[var(--border)] bg-[var(--surface)]/85 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-lg bg-brand-900 text-white">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                    <path d="M12 3 4 7v6c0 4.5 3.4 7.3 8 8 4.6-.7 8-3.5 8-8V7l-8-4Z" stroke-linejoin="round"/>
                    <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="text-sm font-bold tracking-tight text-ink-900 dark:text-ink-50">E-Supervisi Klinis Pendidikan</span>
        </a>
        <nav class="flex items-center gap-2 text-sm">
            <a href="#tahap" class="hidden rounded-lg px-3 py-1.5 font-medium text-ink-600 hover:bg-ink-100 sm:block dark:text-ink-300 dark:hover:bg-ink-800">Alur</a>
            <a href="#prinsip" class="hidden rounded-lg px-3 py-1.5 font-medium text-ink-600 hover:bg-ink-100 sm:block dark:text-ink-300 dark:hover:bg-ink-800">Prinsip</a>
            <x-ui.button as="a" href="{{ route('login') }}" size="sm">Masuk</x-ui.button>
        </nav>
    </div>
</header>

{{-- ── Hero ───────────────────────────────────────────────── --}}
<section class="relative overflow-hidden bg-brand-900 text-brand-50">
    <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>
    <div class="absolute -right-40 -top-40 size-[32rem] rounded-full bg-brand-700/40 blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-brand-200">Sistem E-Supervisi Klinis Pendidikan · Versi 2.0</p>
        <h1 class="mt-4 max-w-3xl text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
            Satu platform untuk keenam tahap siklus supervisi klinis.
        </h1>
        <p class="mt-5 max-w-2xl text-base leading-relaxed text-brand-100 sm:text-lg">
            Dari perencanaan hingga pelaporan berbasis data — dibangun <em>evidence-informed</em> mengacu
            kerangka Acheson &amp; Gall (1997), dengan mode luring untuk aktivitas lapangan di wilayah
            berinfrastruktur terbatas.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50">Masuk ke akun</a>
            <a href="#tahap" class="inline-flex items-center rounded-lg bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 transition hover:bg-white/15">Pelajari alurnya</a>
        </div>

        {{-- mini stepper --}}
        <ol class="mt-14 grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($tahap as $t)
                <li class="flex items-start gap-2.5">
                    <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold">{{ $t['no'] }}</span>
                    <span class="text-sm font-medium leading-tight text-brand-50">{{ $t['nama'] }}</span>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ── 6 tahap ────────────────────────────────────────────── --}}
<section id="tahap" class="scroll-mt-16 bg-[var(--surface-muted)] py-20 lg:py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-700 dark:text-brand-400">Alur Operasional</p>
        <h2 class="mt-1.5 text-3xl font-bold tracking-tight text-ink-900 dark:text-ink-50">Enam tahap, satu bahasa</h2>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[var(--text-muted)]">
            Setiap modul, setiap status siklus, dan setiap kebutuhan data ekstraksi riset dapat ditelusuri
            balik ke salah satu dari enam tahap ini.
        </p>

        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tahap as $t)
                <div class="accent-bar text-brand-600 rounded-xl border border-[var(--border)] bg-[var(--surface)] pl-5 pr-4 py-4 shadow-xs">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded-md bg-brand-100 text-xs font-bold text-brand-800 dark:bg-brand-900 dark:text-brand-100">{{ $t['no'] }}</span>
                        <span class="text-[0.7rem] font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ $t['tahap'] }}</span>
                    </div>
                    <h3 class="mt-2 font-semibold text-ink-900 dark:text-ink-50">{{ $t['nama'] }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-[var(--text-muted)]">{{ $t['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Prinsip ────────────────────────────────────────────── --}}
<section id="prinsip" class="scroll-mt-16 bg-[var(--surface)] py-20 lg:py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-700 dark:text-brand-400">Prinsip Non-Negosiasi</p>
        <h2 class="mt-1.5 text-3xl font-bold tracking-tight text-ink-900 dark:text-ink-50">Dirancang untuk dipercaya</h2>

        <div class="mt-10 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($prinsip as $p)
                <div>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-950/50 dark:text-brand-300">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 13 4 4L19 7"/></svg>
                    </div>
                    <h3 class="mt-3 font-semibold text-ink-900 dark:text-ink-50">{{ $p['judul'] }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-[var(--text-muted)]">{{ $p['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Aktor ──────────────────────────────────────────────── --}}
<section class="bg-[var(--surface-muted)] py-20 lg:py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-700 dark:text-brand-400">Aktor</p>
        <h2 class="mt-1.5 text-3xl font-bold tracking-tight text-ink-900 dark:text-ink-50">Peran yang jelas di setiap siklus</h2>

        <div class="mt-10 grid gap-4 sm:grid-cols-2">
            @foreach ($aktor as $a)
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-5 shadow-xs">
                    <h3 class="font-semibold text-ink-900 dark:text-ink-50">{{ $a['peran'] }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-[var(--text-muted)]">{{ $a['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Konteks riset ──────────────────────────────────────── --}}
<section class="bg-[var(--surface)] py-20 lg:py-24">
    <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-700 dark:text-brand-400">Konteks Riset</p>
        <h2 class="mt-1.5 text-2xl font-bold tracking-tight text-ink-900 dark:text-ink-50">Artefak untuk Design Science Research</h2>
        <p class="mt-3 text-sm leading-relaxed text-[var(--text-muted)]">
            Sistem ini adalah artefak kerja Artikel 3 (pengembangan sistem, DSR), disusun konsisten dengan
            kerangka teoretis Artikel 1 (Systematic Literature Review) dan cakupan instrumen Artikel 2
            (validasi Format A–E). Sebagian keputusan modul bersifat <em>provisional</em> — akan direvisi
            secara <em>additive</em> setelah evidence map SLR final.
        </p>
    </div>
</section>

{{-- ── CTA ────────────────────────────────────────────────── --}}
<section class="bg-brand-900 py-16 text-center text-brand-50">
    <div class="mx-auto max-w-2xl px-4 sm:px-6">
        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Masuk untuk memulai siklus supervisi</h2>
        <p class="mt-2 text-sm text-brand-100">Gunakan kredensial dari Admin Sekolah atau Dinas Pendidikan Anda.</p>
        <div class="mt-6 flex justify-center">
            <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50">Masuk ke akun</a>
        </div>
    </div>
</section>

{{-- ── Footer ─────────────────────────────────────────────── --}}
<footer class="border-t border-[var(--border)] bg-[var(--surface)] py-10">
    <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 text-sm text-[var(--text-muted)] sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p>&copy; {{ date('Y') }} E-Supervisi Klinis Pendidikan. Dibangun dari nol, evidence-informed.</p>
        <p class="text-xs">Data guru diperlakukan sesuai UU No. 27/2022 tentang Pelindungan Data Pribadi.</p>
    </div>
</footer>

</body>
</html>
