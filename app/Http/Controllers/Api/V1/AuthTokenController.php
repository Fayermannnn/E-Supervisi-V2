<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Audit\AuditLogger;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Token perangkat untuk PWA luring (ADR-007). Scope minimal, dapat dicabut
 * per perangkat.
 */
class AuthTokenController extends ApiController
{
    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        if (! Auth::validate(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $user */
        $user = User::query()->where('email', $data['email'])->sole();

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => 'Akun dinonaktifkan.']);
        }

        $abilities = [];
        if ($user->can(Permission::SyncObservation->value)) {
            $abilities[] = 'observation:sync';
        }
        if ($user->can(Permission::UploadFollowUpEvidence->value)) {
            $abilities[] = 'follow-up:evidence';
        }

        $token = $user->createToken($data['device_name'], $abilities, now()->addDays(30));

        $audit->log('auth.device_token_issued', $user, context: ['device' => $data['device_name']], actor: $user);

        return $this->ok([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ], status: 201);
    }

    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $user->tokens()->whereKey($tokenId)->delete();

        return $this->ok(['revoked' => true]);
    }
}
