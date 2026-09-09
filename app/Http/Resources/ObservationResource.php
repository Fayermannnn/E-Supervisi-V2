<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Observation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Observation
 */
class ObservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cycle_id' => $this->cycle_id,
            'instrument_version_id' => $this->instrument_version_id,
            'tipe' => $this->tipe->value,
            'status' => $this->status->value,
            'version' => $this->version,
            'catatan_skrip' => $this->catatan_skrip,
            'mulai_at' => $this->mulai_at?->toIso8601String(),
            'selesai_at' => $this->selesai_at?->toIso8601String(),
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'responses' => $this->whenLoaded('responses', fn () => $this->responses->map(fn ($r) => [
                'item_key' => $r->item_key,
                'section_key' => $r->section_key,
                'value_numeric' => $r->value_numeric,
                'value_boolean' => $r->value_boolean,
                'value_text' => $r->value_text,
                'value_json' => $r->value_json,
                'catatan_item' => $r->catatan_item,
            ])),
        ];
    }
}
