<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Titik registrasi lintas-domain untuk modular monolith.
 *
 * Setiap domain di app/Domain/* mendaftarkan binding, policy, event listener,
 * route, dan migration-nya melalui service provider domain sendiri yang
 * dipanggil dari sini seiring modul bertambah (Fase 1+).
 *
 * Lihat docs/architecture.md (ADR-001, ADR-002).
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * Service provider milik tiap domain.
     *
     * @var list<class-string<ServiceProvider>>
     */
    protected array $domainProviders = [
        // \App\Domain\Identity\IdentityServiceProvider::class,
        // \App\Domain\Audit\AuditServiceProvider::class,
        // ...
    ];

    public function register(): void
    {
        foreach ($this->domainProviders as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
        //
    }
}
