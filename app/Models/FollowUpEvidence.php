<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain FollowUp
 *
 * @property string $follow_up_item_id
 * @property string $diunggah_oleh
 * @property string $tipe
 * @property string|null $disk
 * @property string|null $path
 * @property string|null $url
 * @property string|null $deskripsi
 * @property string $upload_status
 */
class FollowUpEvidence extends Model
{
    use HasUuids;

    protected $table = 'follow_up_evidence';

    // UUID dibuat klien (bukti bisa diantre luring, R-04).
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'follow_up_item_id',
        'diunggah_oleh',
        'tipe',
        'disk',
        'path',
        'original_name',
        'deskripsi',
        'url',
        'upload_status',
        'captured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['captured_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<FollowUpItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(FollowUpItem::class, 'follow_up_item_id');
    }
}
