<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SupervisionCycle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SupervisionCycle
 */
class CycleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'stage' => $this->status->stage(),
            'tahun_ajaran' => $this->tahun_ajaran,
            'semester' => $this->semester,
            'fokus_ringkas' => $this->fokus_ringkas,
            'guru' => $this->whenLoaded('guru', fn () => ['id' => $this->guru?->id, 'nama' => $this->guru?->name]),
            'supervisor' => $this->whenLoaded('supervisor', fn () => ['id' => $this->supervisor?->id, 'nama' => $this->supervisor?->name]),
            'planning_agreement' => $this->whenLoaded('planningAgreement', fn () => $this->planningAgreement ? [
                'fokus_observasi' => $this->planningAgreement->fokus_observasi,
                'instrument_version_id' => $this->planningAgreement->instrument_version_id,
                'tipe_observasi' => $this->planningAgreement->tipe_observasi->value,
                'jadwal_mulai' => $this->planningAgreement->jadwal_mulai->toIso8601String(),
                'fully_agreed' => $this->planningAgreement->isFullyAgreed(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
