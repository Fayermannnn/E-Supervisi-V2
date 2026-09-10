<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\ProfessionalDev\Actions\CurateBestPractice;
use App\Domain\ProfessionalDev\Actions\GeneratePkbRecommendations;
use App\Domain\ProfessionalDev\Actions\NominateBestPractice;
use App\Domain\ProfessionalDev\Actions\RespondBestPracticeConsent;
use App\Domain\ProfessionalDev\Actions\RespondPkbRecommendation;
use App\Models\BestPractice;
use App\Models\PkbCatalogItem;
use App\Models\PkbRecommendation;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Katalog PKB (M9) & Perpustakaan Praktik Baik (M10). @provisional.
 */
class ProfessionalDevController extends ApiController
{
    public function catalog(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $items = PkbCatalogItem::query()
            ->availableFor($user->resolveDinasId() ?? $user->adminDinasId())
            ->orderBy('judul')
            ->get()
            ->map(fn (PkbCatalogItem $i): array => [
                'id' => $i->id,
                'judul' => $i->judul,
                'tipe' => $i->tipe,
                'penyelenggara' => $i->penyelenggara,
                'tags' => $i->tags ?? [],
                'tautan' => $i->tautan,
            ])->all();

        return $this->ok($items);
    }

    public function generateRecommendations(Request $request, SupervisionCycle $cycle, GeneratePkbRecommendations $action): JsonResponse
    {
        try {
            $result = $action->handle($this->user($request), $cycle);
        } catch (AuthorizationException $e) {
            return $this->fail(['pkb' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['pkb' => [$e->getMessage()]]);
        }

        return $this->ok($result, status: 201);
    }

    public function respondRecommendation(Request $request, PkbRecommendation $recommendation, RespondPkbRecommendation $action): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:dipilih,ditolak,selesai']]);

        try {
            $recommendation = $action->handle($this->user($request), $recommendation, $data['status']);
        } catch (AuthorizationException $e) {
            return $this->fail(['pkb' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['pkb' => [$e->getMessage()]]);
        }

        return $this->ok(['status' => $recommendation->status]);
    }

    public function nominateBestPractice(Request $request, SupervisionCycle $cycle, NominateBestPractice $action): JsonResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'ringkasan' => ['required', 'string', 'max:2000'],
            'praktik' => ['required', 'string', 'max:20000'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'anonim' => ['sometimes', 'boolean'],
        ]);

        try {
            $bp = $action->handle($this->user($request), $cycle, $data);
        } catch (AuthorizationException $e) {
            return $this->fail(['best_practice' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['best_practice' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $bp->id, 'status' => $bp->status], status: 201);
    }

    public function consentBestPractice(Request $request, BestPractice $bestPractice, RespondBestPracticeConsent $action): JsonResponse
    {
        $data = $request->validate(['setuju' => ['required', 'boolean']]);

        try {
            $bestPractice = $action->handle($this->user($request), $bestPractice, (bool) $data['setuju']);
        } catch (AuthorizationException $e) {
            return $this->fail(['best_practice' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['best_practice' => [$e->getMessage()]]);
        }

        return $this->ok(['status' => $bestPractice->status]);
    }

    public function curateBestPractice(Request $request, BestPractice $bestPractice, CurateBestPractice $action): JsonResponse
    {
        $data = $request->validate([
            'terbit' => ['required', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $bestPractice = $action->handle($this->user($request), $bestPractice, (bool) $data['terbit'], $data['catatan'] ?? null);
        } catch (AuthorizationException $e) {
            return $this->fail(['best_practice' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['best_practice' => [$e->getMessage()]]);
        }

        return $this->ok(['status' => $bestPractice->status]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
