<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Header keamanan respons
    |--------------------------------------------------------------------------
    |
    | Ditegakkan oleh App\Http\Middleware\SecureHeaders pada setiap respons.
    | Sumber: Spec §10 (keamanan/PDP), docs/deployment.md,
    | docs/technical-evaluation.md §1 (sisa pekerjaan keamanan pra-go-live).
    |
    */

    'headers' => [
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),
        'content_type_options' => 'nosniff',
        'cross_origin_opener_policy' => env('SECURITY_COOP', 'same-origin'),
        'cross_origin_resource_policy' => env('SECURITY_CORP', 'same-origin'),
        'permitted_cross_domain_policies' => 'none',

        // Matikan API browser yang tidak dipakai artefak (kamera, mikrofon,
        // geolokasi, pembayaran, dst.). Konsol observasi luring hanya memakai
        // navigator.onLine + IndexedDB — tidak butuh perangkat media.
        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'accelerometer=(), autoplay=(), camera=(), display-capture=(), '
            .'encrypted-media=(), fullscreen=(self), geolocation=(), gyroscope=(), '
            .'magnetometer=(), microphone=(), midi=(), payment=(), usb=()',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security
    |--------------------------------------------------------------------------
    |
    | Hanya dikirim pada koneksi HTTPS (dicek middleware via $request->secure();
    | di belakang reverse proxy butuh TrustProxies dikonfigurasi). Aktifkan di
    | produksi setelah TLS terpasang dan seluruh subdomain melayani HTTPS.
    |
    */

    'hsts' => [
        'enabled' => env('SECURITY_HSTS_ENABLED', false),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31_536_000),
        'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => env('SECURITY_HSTS_PRELOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | 'script-src' sengaja memuat 'unsafe-eval': Alpine (dibundel Livewire 3)
    | mengevaluasi ekspresi direktif lewat konstruktor Function. Setiap inline
    | <script> aplikasi memakai nonce per-request yang disuntik middleware ke
    | Vite::useCspNonce() — sehingga @vite, @fonts, dan aset Livewire ikut
    | ber-nonce dan 'unsafe-inline' TIDAK diperlukan untuk script.
    |
    | 'style-src' tetap 'unsafe-inline' (atribut style Tailwind/Alpine; risiko
    | injeksi lewat style jauh lebih rendah daripada lewat script).
    |
    | Dua inline <script> statis di layout (boot tema anti-FOUC di <head>, dan
    | registrasi service worker) tidak bisa ber-nonce: Livewire `wire:navigate`
    | menyuntik ulang <script> lintas-halaman sehingga nonce dari respons lama
    | tak lagi cocok. Keduanya di-whitelist lewat hash SHA-256 di 'script_hashes'
    | (byte-stable; diuji di tests/Feature/Security/SecureHeadersTest).
    |
    | Saat `npm run dev` (Vite hot), middleware menambahkan origin dev-server +
    | websocket HMR secara otomatis — tidak perlu diatur di sini.
    |
    */

    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
        'report_uri' => env('SECURITY_CSP_REPORT_URI'),

        // Hash SHA-256 inline <script> statis (tanpa nonce). Ditambahkan
        // middleware ke 'script-src'. Urutan: [boot tema, registrasi SW].
        'script_hashes' => [
            "'sha256-6gEgD8sBzhjvpvGou1XzcTVW+qJlaNuMqgHARRMbAQs='",
            "'sha256-mwiEfrB+/V/X9yX7eojpe6wPP+86hVQG5RUGoVpGAsY='",
        ],

        'directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'connect-src' => ["'self'"],
            'font-src' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'frame-src' => ["'none'"],
            'img-src' => ["'self'", 'data:'],
            'manifest-src' => ["'self'"],
            'object-src' => ["'none'"],
            'script-src' => ["'self'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'worker-src' => ["'self'"],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS di produksi
    |--------------------------------------------------------------------------
    |
    | Paksa Illuminate\Support\Facades\URL menghasilkan skema https:// —
    | penting di balik proxy TLS-terminating (Nginx) tanpa header
    | X-Forwarded-Proto tepercaya. Diterapkan di AppServiceProvider::boot().
    |
    | Proxy tepercaya (env TRUSTED_PROXIES) diatur LANGSUNG via env() di
    | bootstrap/app.php, BUKAN lewat config di sini — closure
    | `withMiddleware()` dieksekusi pada tahap bootstrap (termasuk harness
    | Larastan) sebelum container 'config' terdaftar. Lihat komentar di sana;
    | nilainya: null/kosong (default, aman untuk `php artisan serve` lokal) —
    | atau '*'/daftar IP dipisah koma di produksi (VPS tunggal + Nginx,
    | docs/deployment.md) supaya $request->secure() membaca X-Forwarded-Proto
    | asli — tanpa ini HSTS TIDAK PERNAH terkirim walau
    | SECURITY_HSTS_ENABLED=true (koneksi app↔Nginx di localhost = HTTP).
    |
    */

    'force_https' => env('FORCE_HTTPS', false),

];
