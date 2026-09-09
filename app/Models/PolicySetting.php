<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Administration
 *
 * @property string $key
 * @property mixed $value
 * @property string|null $dinas_id
 * @property string|null $description
 */
class PolicySetting extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = ['dinas_id', 'key', 'value', 'description', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    /**
     * @return BelongsTo<Dinas, $this>
     */
    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class);
    }
}
