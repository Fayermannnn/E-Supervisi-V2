<?php

declare(strict_types=1);

it('sends baseline security headers on html responses', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');

    expect($response->headers->get('Permissions-Policy'))
        ->toContain('camera=()')
        ->toContain('microphone=()')
        ->toContain('geolocation=()')
        ->toContain('payment=()');
});

it('sends an enforced content security policy with a per-request nonce', function () {
    $policy = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($policy)
        ->not->toBeNull()
        ->toContain("default-src 'self'")
        ->toContain("base-uri 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("form-action 'self'")
        ->toMatch("/script-src [^;]*'nonce-[A-Za-z0-9]+'/");

    $scriptSrc = collect(explode(';', $policy))
        ->map(fn (string $directive): string => trim($directive))
        ->first(fn (string $directive): bool => str_starts_with($directive, 'script-src'));

    expect($scriptSrc)
        ->toContain("'unsafe-eval'")   // Alpine (dibundel Livewire) butuh ini
        ->not->toContain("'unsafe-inline'");
});

it('whitelists the static inline layout scripts by hash, matching the rendered markup', function () {
    $response = $this->get('/login');
    $policy = (string) $response->headers->get('Content-Security-Policy');
    $html = $response->getContent();

    // Setiap inline <script> di halaman harus lolos: punya nonce yang cocok,
    // atau hash-nya terdaftar di script-src.
    preg_match_all('/<script\b(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/s', (string) $html, $matches);
    expect($matches[1])->not->toBeEmpty();

    preg_match("/'nonce-([A-Za-z0-9]+)'/", $policy, $nonceMatch);
    $nonce = $nonceMatch[1] ?? '';

    foreach ($matches[0] as $index => $tag) {
        $body = $matches[1][$index];

        if (trim($body) === '') {
            continue; // <script src> yang lolos regex negatif-lookahead tak akan sampai sini
        }

        $hash = "'sha256-".base64_encode(hash('sha256', $body, true))."'";
        $hasNonce = $nonce !== '' && str_contains($tag, 'nonce="'.$nonce.'"');

        expect($hasNonce || str_contains($policy, $hash))->toBeTrue(
            "Inline script #{$index} tidak ber-nonce dan hash-nya ({$hash}) tidak ada di script-src.",
        );
    }
});

it('rotates the csp nonce on every request', function () {
    $first = $this->get('/login')->headers->get('Content-Security-Policy');
    $second = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($first)->not->toBe($second);
});

it('covers the public landing page inline script by the same hash', function () {
    $response = $this->get('/');
    $policy = (string) $response->headers->get('Content-Security-Policy');

    preg_match('/<script\b(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/s', (string) $response->getContent(), $match);
    $hash = "'sha256-".base64_encode(hash('sha256', $match[1] ?? '', true))."'";

    expect($policy)->toContain($hash);
});

it('embeds the policy nonce in the rendered inline scripts', function () {
    $response = $this->get('/login');

    preg_match("/'nonce-([A-Za-z0-9]+)'/", (string) $response->headers->get('Content-Security-Policy'), $matches);

    expect($matches[1] ?? null)->not->toBeNull();
    $response->assertSee('nonce="'.$matches[1].'"', escape: false);
});

it('omits HSTS on plain http but sends it on https when enabled', function () {
    config(['security.hsts.enabled' => true]);

    $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');

    expect($this->get('https://localhost/login')->headers->get('Strict-Transport-Security'))
        ->toContain('max-age=31536000')
        ->toContain('includeSubDomains');
});

it('does not attach a content security policy to json api responses', function () {
    $response = $this->getJson('/api/v1/cycles');

    $response->assertUnauthorized();
    $response->assertHeaderMissing('Content-Security-Policy');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('can switch the policy to report-only mode', function () {
    config(['security.csp.report_only' => true]);

    $response = $this->get('/login');

    $response->assertHeaderMissing('Content-Security-Policy');
    expect($response->headers->get('Content-Security-Policy-Report-Only'))
        ->toContain("default-src 'self'");
});

it('can be disabled entirely via config', function () {
    config(['security.csp.enabled' => false]);

    $response = $this->get('/login');

    $response->assertHeaderMissing('Content-Security-Policy');
    $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    // Header statis lain tetap terkirim.
    $response->assertHeader('X-Frame-Options', 'DENY');
});
