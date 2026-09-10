<?php

declare(strict_types=1);

namespace App\Domain\Accountability;

use App\Domain\Administration\PolicySettings;
use App\Models\SupervisorEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agregasi penilaian 360° dengan ambang anonimitas (M11). Respons individual
 * tidak pernah dikembalikan — hanya rata-rata per dimensi bila jumlah respons
 * memenuhi `accountability.min_responses`.
 */
class AccountabilityAggregator
{
    public function __construct(private readonly PolicySettings $policy) {}

    /**
     * @return array{cukup: bool, min: int, responden: int, dimensi: array<string, float>, rata_keseluruhan: float|null}
     */
    public function forSupervisor(User $supervisor): array
    {
        return $this->aggregate(
            SupervisorEvaluation::query()->where('supervisor_id', $supervisor->getKey()),
            $supervisor->resolveDinasId(),
        );
    }

    /**
     * @return array{
     *     cukup: bool, min: int, responden: int,
     *     dimensi: array<string, float>, rata_keseluruhan: float|null,
     *     per_supervisor: list<array{supervisor: string, cukup: bool, responden: int, dimensi: array<string, float>, rata_keseluruhan: float|null}>
     * }
     */
    public function forDinas(string $dinasId): array
    {
        $overall = $this->aggregate(SupervisorEvaluation::query()->where('dinas_id', $dinasId), $dinasId);

        $perSupervisor = [];
        $rows = SupervisorEvaluation::query()
            ->where('dinas_id', $dinasId)
            ->get()
            ->groupBy('supervisor_id');

        foreach ($rows as $supervisorId => $group) {
            $supervisorUser = User::find($supervisorId);
            $name = $supervisorUser !== null ? $supervisorUser->name : 'Supervisor';
            $agg = $this->summarize($group->values()->all(), $dinasId);
            $perSupervisor[] = [
                'supervisor' => $name,
                'cukup' => $agg['cukup'],
                'responden' => $agg['responden'],
                'dimensi' => $agg['dimensi'],
                'rata_keseluruhan' => $agg['rata_keseluruhan'],
            ];
        }

        return [...$overall, 'per_supervisor' => $perSupervisor];
    }

    /**
     * @param  Builder<SupervisorEvaluation>  $query
     * @return array{cukup: bool, min: int, responden: int, dimensi: array<string, float>, rata_keseluruhan: float|null}
     */
    private function aggregate(Builder $query, ?string $dinasId): array
    {
        return $this->summarize($query->get()->values()->all(), $dinasId);
    }

    /**
     * @param  array<int, SupervisorEvaluation>  $evaluations
     * @return array{cukup: bool, min: int, responden: int, dimensi: array<string, float>, rata_keseluruhan: float|null}
     */
    private function summarize(array $evaluations, ?string $dinasId): array
    {
        $min = (int) $this->policy->get('accountability.min_responses', $dinasId);
        $count = count($evaluations);

        if ($count < $min) {
            return ['cukup' => false, 'min' => $min, 'responden' => $count, 'dimensi' => [], 'rata_keseluruhan' => null];
        }

        $sums = [];
        foreach (array_keys(SupervisionProcessSurvey::dimensions()) as $key) {
            $sums[$key] = 0.0;
        }

        foreach ($evaluations as $evaluation) {
            foreach ($sums as $key => $_) {
                $sums[$key] += (float) ($evaluation->jawaban[$key] ?? 0);
            }
        }

        $dimensi = [];
        foreach ($sums as $key => $sum) {
            $dimensi[$key] = round($sum / $count, 2);
        }

        $overall = round(array_sum($dimensi) / count($dimensi), 2);

        return ['cukup' => true, 'min' => $min, 'responden' => $count, 'dimensi' => $dimensi, 'rata_keseluruhan' => $overall];
    }
}
