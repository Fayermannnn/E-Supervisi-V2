<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ai\Actions\ReviewAiGeneration;
use App\Models\AiGeneration;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AiGenerationController extends ApiController
{
    /**
     * POST /ai/generations/{generation}/review — supervisor accept/edit/reject.
     */
    public function review(Request $request, AiGeneration $generation, ReviewAiGeneration $action): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accept,edit,reject'],
            'edited_output' => ['nullable', 'string', 'max:20000', 'required_if:decision,edit'],
        ]);

        try {
            $generation = $action->handle($this->user($request), $generation, $data['decision'], $data['edited_output'] ?? null);
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['review' => [$e->getMessage()]]);
        }

        return $this->ok([
            'review_status' => $generation->review_status,
            'human_approved' => $generation->isHumanApproved(),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
