<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Header keamanan respons (CSP nonce per-request, HSTS, X-Frame-Options,
        // dst.) pada setiap request. Konfigurasi: config/security.php.
        $middleware->append(App\Http\Middleware\SecureHeaders::class);

        // Percayai reverse proxy (Nginx, VPS tunggal — docs/deployment.md) agar
        // $request->secure()/->ip() membaca header X-Forwarded-* asli, bukan
        // koneksi lokal proxy↔app. Tanpa ini HSTS tak pernah terkirim di
        // produksi. Kosong secara default (env TRUSTED_PROXIES tak diset) =
        // perilaku asli Laravel, aman untuk `php artisan serve` lokal.
        //
        // env() langsung (bukan config('security.trusted_proxies')) karena
        // closure ini dieksekusi sebagian bootstrap (termasuk harness Larastan)
        // SEBELUM container 'config' terdaftar — lihat config/security.php
        // untuk salinan terdokumentasi nilai yang sama.
        $trustedProxies = env('TRUSTED_PROXIES');
        if (is_string($trustedProxies) && $trustedProxies !== '') {
            $middleware->trustProxies(
                at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)),
                headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
            );
        }

        // Sanctum: request same-origin dari SPA/Livewire terautentikasi via sesi;
        // perangkat PWA memakai bearer token (ADR-007).
        $middleware->statefulApi();

        $middleware->alias([
            'abilities' => Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
