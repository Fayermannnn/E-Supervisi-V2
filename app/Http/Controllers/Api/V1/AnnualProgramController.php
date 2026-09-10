<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Program\Actions\GenerateProgramCycles;
use App\Domain\Program\Actions\SaveAnnualProgram;
use App\Domain\Program\Actions\SyncProgramTargets;
use App\Http\Requests\Api\SaveAnnualProgramRequest;
use App\Http\Requests\Api\SyncProgramTargetsRequest;
use App\Models\AnnualProgram;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Program Supervisi Tahunan (M7). Controller = shell tipis atas Action domain.
 */
class AnnualProgramController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $programs = [];

        foreach (AnnualProgram::query()->ownedBy($this->user($request))->latest()->get() as $program) {
            $targets = $program->targets()->get();
            $programs[] = [
                'id' => $program->id,
                'judul' => $program->judul,
                'tahun_ajaran' => $program->tahun_ajaran,
                'semester' => $program->semester,
                'status' => $program->status,
                'targets' => $targets->count(),
                'generated' => $targets->whereNotNull('cycle_id')->count(),
            ];
        }

        return $this->ok($programs);
    }

    public function store(SaveAnnualProgramRequest $request, SaveAnnualProgram $action): JsonResponse
    {
        try {
            $program = $action->handle($this->user($request), null, $request->programData());
        } catch (DomainException $e) {
            return $this->fail(['program' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $program->id], status: 201);
    }

    public function show(Request $request, AnnualProgram $program): JsonResponse
    {
        $this->authorize('view', $program);

        $targets = [];
        foreach ($program->targets()->get() as $target) {
            $targets[] = [
                'id' => $target->id,
                'guru_id' => $target->guru_id,
                'guru' => $target->guru()->sole()->name,
                'fokus_ringkas' => $target->fokus_ringkas,
                'cycle_id' => $target->cycle_id,
                'generated_at' => $target->generated_at,
            ];
        }

        return $this->ok([
            'id' => $program->id,
            'judul' => $program->judul,
            'tahun_ajaran' => $program->tahun_ajaran,
            'semester' => $program->semester,
            'status' => $program->status,
            'catatan' => $program->catatan,
            'targets' => $targets,
        ]);
    }

    public function update(SaveAnnualProgramRequest $request, AnnualProgram $program, SaveAnnualProgram $action): JsonResponse
    {
        $this->authorize('update', $program);

        try {
            $action->handle($this->user($request), $program, $request->programData());
        } catch (DomainException $e) {
            return $this->fail(['program' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $program->id]);
    }

    public function targets(SyncProgramTargetsRequest $request, AnnualProgram $program, SyncProgramTargets $action): JsonResponse
    {
        $this->authorize('update', $program);

        try {
            $action->handle($this->user($request), $program, $request->targets());
        } catch (DomainException $e) {
            return $this->fail(['targets' => [$e->getMessage()]]);
        }

        return $this->ok(['targets' => $program->targets()->count()]);
    }

    public function generate(Request $request, AnnualProgram $program, GenerateProgramCycles $action): JsonResponse
    {
        $this->authorize('update', $program);

        try {
            $result = $action->handle($this->user($request), $program);
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['program' => [$e->getMessage()]]);
        }

        return $this->ok($result, status: 201);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
