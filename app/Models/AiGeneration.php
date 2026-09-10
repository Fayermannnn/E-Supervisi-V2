<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Jejak setiap keluaran AI (ADR-009). review_status TIDAK PERNAH otomatis
 * `accepted` — hanya manusia yang menyetujui.
 *
 * @domain Ai
 *
 * @property string $provider
 * @property string|null $model
 * @property string $prompt_key
 * @property int $prompt_version
 * @property string $source_type
 * @property string $source_id
 * @property array<string, mixed>|null $input_context
 * @property string|null $output
 * @property string $status
 * @property string $review_status
 * @property string|null $reviewed_output
 * @property string|null $reviewer_id
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 */
class AiGeneration extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FAILED = 'failed';

    public const REVIEW_DRAFT = 'draft';

    public const REVIEW_ACCEPTED = 'accepted';

    public const REVIEW_EDITED = 'edited';

    public const REVIEW_REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'model',
        'prompt_key',
        'prompt_version',
        'source_type',
        'source_id',
        'input_context',
        'output',
        'status',
        'review_status',
        'reviewed_output',
        'reviewer_id',
        'generated_at',
        'reviewed_at',
        'token_usage',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_version' => 'integer',
            'input_context' => 'array',
            'token_usage' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Teks yang boleh dipakai manusia: hasil suntingan bila ada, jika tidak
     * output mentah — TAPI hanya bila sudah ditinjau.
     */
    public function usableText(): ?string
    {
        if (! $this->isHumanApproved()) {
            return null;
        }

        return $this->reviewed_output ?? $this->output;
    }

    public function isHumanApproved(): bool
    {
        return in_array($this->review_status, [self::REVIEW_ACCEPTED, self::REVIEW_EDITED], true)
            && $this->reviewer_id !== null;
    }
}
