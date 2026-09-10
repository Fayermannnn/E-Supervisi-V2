<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @domain Ai
 *
 * @property string $key
 * @property int $version
 * @property string $template
 * @property array<string, mixed>|null $variables
 * @property bool $is_active
 */
class AiPromptTemplate extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'version', 'template', 'variables', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function active(string $key): ?self
    {
        return static::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }
}
