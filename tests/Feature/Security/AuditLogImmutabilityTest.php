<?php

declare(strict_types=1);

use App\Domain\Audit\AuditLogger;
use App\Models\AuditLog;
use App\Models\User;

it('creates audit rows but forbids updating them', function () {
    $log = app(AuditLogger::class)->log('test.event');

    expect(fn () => $log->update(['action' => 'tampered']))
        ->toThrow(RuntimeException::class);

    expect($log->fresh()->action)->toBe('test.event');
});

it('forbids deleting audit rows', function () {
    $log = app(AuditLogger::class)->log('test.event');

    expect(fn () => $log->delete())->toThrow(RuntimeException::class);

    expect(AuditLog::whereKey($log->getKey())->exists())->toBeTrue();
});

it('exposes no HTTP route that mutates audit logs', function () {
    $mutating = collect(app('router')->getRoutes())->filter(function ($route) {
        return str_contains($route->uri(), 'audit')
            && array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [];
    });

    expect($mutating)->toBeEmpty();
});

it('records the acting user and role on a logged event', function () {
    $user = User::factory()->create();
    $user->assignRole(App\Support\Enums\Role::AdminSistem);
    $this->actingAs($user);

    $log = app(AuditLogger::class)->log('test.event');

    expect($log->actor_id)->toBe($user->getKey())
        ->and($log->actor_role)->toContain('admin_sistem');
});
