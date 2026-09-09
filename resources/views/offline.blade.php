<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Luring — E-Supervisi</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center p-6">
    <div class="max-w-sm text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-status-progress/15 text-status-progress">
            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M1 1l22 22M16.7 16.7A9 9 0 0 1 5 5m3-1.5A9 9 0 0 1 21 12" stroke-linecap="round"/></svg>
        </div>
        <h1 class="mt-4 text-lg font-semibold text-ink-900">Anda sedang luring</h1>
        <p class="mt-1 text-sm text-ink-500">
            Halaman ini butuh koneksi. Isian observasi yang sudah Anda kerjakan tersimpan
            di perangkat dan akan tersinkron otomatis saat koneksi kembali.
        </p>
        <button onclick="location.reload()" class="mt-4 rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white">Coba lagi</button>
    </div>
</body>
</html>
