<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @domain Support
 */
class HelpArticle extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'category',
        'body_markdown',
        'position',
        'is_published',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
