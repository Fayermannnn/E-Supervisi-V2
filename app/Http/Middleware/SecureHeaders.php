<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menegakkan header keamanan respons: Content-Security-Policy (nonce
 * per-request), HSTS (khusus HTTPS), X-Frame-Options, X-Content-Type-Options,
 * Referrer-Policy, Permissions-Policy, dan Cross-Origin-*-Policy.
 *
 * Sumber: Spec §10, docs/deployment.md, docs/technical-evaluation.md §1.
 *
 * Nonce dibangkitkan SEBELUM view dirender (via Vite::useCspNonce) supaya
 * direktif @vite, @fonts, dan aset yang disuntik Livewire ikut ber-nonce.
 * Konfigurasi: config/security.php.
 */
class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $this->applyStaticHeaders($response);
        $this->applyHsts($request, $response);
        $this->applyContentSecurityPolicy($response, $nonce);

        return $response;
    }

    private function applyStaticHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', $this->configString('security.headers.content_type_options', 'nosniff'));
        $response->headers->set('X-Frame-Options', $this->configString('security.headers.frame_options', 'DENY'));
        $response->headers->set('Referrer-Policy', $this->configString('security.headers.referrer_policy', 'strict-origin-when-cross-origin'));
        $response->headers->set('Cross-Origin-Opener-Policy', $this->configString('security.headers.cross_origin_opener_policy', 'same-origin'));
        $response->headers->set('Cross-Origin-Resource-Policy', $this->configString('security.headers.cross_origin_resource_policy', 'same-origin'));
        $response->headers->set('X-Permitted-Cross-Domain-Policies', $this->configString('security.headers.permitted_cross_domain_policies', 'none'));

        $permissionsPolicy = $this->configString('security.headers.permissions_policy');

        if ($permissionsPolicy !== '') {
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }
    }

    private function applyHsts(Request $request, Response $response): void
    {
        if (! $request->secure() || config('security.hsts.enabled') !== true) {
            return;
        }

        $value = 'max-age='.$this->configInt('security.hsts.max_age', 31_536_000);

        if (config('security.hsts.include_subdomains') !== false) {
            $value .= '; includeSubDomains';
        }

        if (config('security.hsts.preload') === true) {
            $value .= '; preload';
        }

        $response->headers->set('Strict-Transport-Security', $value);
    }

    private function applyContentSecurityPolicy(Response $response, string $nonce): void
    {
        if (config('security.csp.enabled') !== true || ! $this->isHtml($response)) {
            return;
        }

        $directives = $this->cspDirectives();
        $directives['script-src'][] = "'nonce-{$nonce}'";

        $hashes = config('security.csp.script_hashes');

        if (is_array($hashes)) {
            foreach ($hashes as $hash) {
                if (is_string($hash) && $hash !== '') {
                    $directives['script-src'][] = $hash;
                }
            }
        }

        if (Vite::isRunningHot()) {
            $this->allowViteDevServer($directives);
        }

        $parts = [];

        foreach ($directives as $name => $sources) {
            $sources = array_values(array_unique($sources));
            $parts[] = $sources === [] ? $name : $name.' '.implode(' ', $sources);
        }

        $reportUri = $this->configString('security.csp.report_uri');

        if ($reportUri !== '') {
            $parts[] = 'report-uri '.$reportUri;
        }

        $header = config('security.csp.report_only') === true
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($header, implode('; ', $parts));
    }

    /**
     * Tambahkan origin Vite dev-server + websocket HMR saat `npm run dev`.
     *
     * @param  array<string, list<string>>  $directives
     */
    private function allowViteDevServer(array &$directives): void
    {
        $hot = @file_get_contents(public_path('hot'));

        $origin = is_string($hot) && trim($hot) !== ''
            ? rtrim(trim($hot), '/')
            : 'http://localhost:5173';

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $origin);

        foreach (['script-src', 'style-src', 'connect-src', 'font-src'] as $directive) {
            $directives[$directive][] = $origin;
        }

        $directives['connect-src'][] = $socket;
    }

    /**
     * @return array<string, list<string>>
     */
    private function cspDirectives(): array
    {
        $configured = config('security.csp.directives');

        if (! is_array($configured)) {
            return ['default-src' => ["'self'"]];
        }

        $directives = [];

        foreach ($configured as $name => $sources) {
            if (is_string($name) && is_array($sources)) {
                $directives[$name] = array_values(array_filter($sources, static fn ($source): bool => is_string($source)));
            }
        }

        return $directives;
    }

    private function isHtml(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');

        return $contentType === null || str_contains(strtolower($contentType), 'text/html');
    }

    private function configString(string $key, string $default = ''): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function configInt(string $key, int $default): int
    {
        $value = config($key);

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : $default);
    }
}
