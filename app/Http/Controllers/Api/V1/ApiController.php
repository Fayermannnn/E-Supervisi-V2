<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * Basis controller API v1. Envelope konsisten { data, meta } / { errors, meta }.
 * Controller API = shell tipis atas Action domain (ADR-008).
 */
abstract class ApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * @param  array<mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    protected function ok(array $data, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => $meta], $status);
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $extra
     */
    protected function fail(array $errors, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(['errors' => $errors, 'meta' => []] + $extra, $status);
    }
}
