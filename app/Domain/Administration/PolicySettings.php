<?php

declare(strict_types=1);

namespace App\Domain\Administration;

use App\Models\PolicySetting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Akses konfigurasi kebijakan dengan penggabungan default global → override
 * dinas (M15). Nilai di-cache dan di-invalidasi saat disimpan.
 */
class PolicySettings
{
    /**
     * Definisi kebijakan yang dikenal sistem: key => [default, deskripsi, tipe].
     *
     * @var array<string, array{default: mixed, description: string, type: string}>
     */
    public const DEFINITIONS = [
        'planning.teacher_reflection_required' => [
            'default' => true,
            'description' => 'Wajibkan guru mengisi refleksi pra-observasi sebelum observasi.',
            'type' => 'boolean',
        ],
        'followup.reminder_days_before' => [
            'default' => [3, 1],
            'description' => 'Hari sebelum tenggat RTL untuk mengirim pengingat.',
            'type' => 'list',
        ],
        'dinas.can_view_cycle_detail' => [
            'default' => false,
            'description' => 'Izinkan Admin Dinas melihat detail siklus per guru (bukan hanya agregat).',
            'type' => 'boolean',
        ],
        'observation.max_media_mb' => [
            'default' => 200,
            'description' => 'Ukuran maksimum berkas media observasi (MB).',
            'type' => 'integer',
        ],
    ];

    public function get(string $key, ?string $dinasId = null): mixed
    {
        $this->assertKnown($key);

        return Cache::remember(
            $this->cacheKey($key, $dinasId),
            now()->addHour(),
            function () use ($key, $dinasId): mixed {
                if ($dinasId !== null) {
                    $scoped = PolicySetting::where('key', $key)->where('dinas_id', $dinasId)->first();
                    if ($scoped !== null) {
                        return $scoped->value;
                    }
                }

                $global = PolicySetting::where('key', $key)->whereNull('dinas_id')->first();

                return $global->value ?? self::DEFINITIONS[$key]['default'];
            },
        );
    }

    public function set(string $key, mixed $value, ?string $dinasId, ?string $updatedBy = null): PolicySetting
    {
        $this->assertKnown($key);

        $setting = PolicySetting::updateOrCreate(
            ['key' => $key, 'dinas_id' => $dinasId],
            [
                'value' => $value,
                'description' => self::DEFINITIONS[$key]['description'],
                'updated_by' => $updatedBy,
            ],
        );

        Cache::forget($this->cacheKey($key, $dinasId));

        return $setting;
    }

    private function assertKnown(string $key): void
    {
        if (! array_key_exists($key, self::DEFINITIONS)) {
            throw new InvalidArgumentException("Kebijakan tidak dikenal: {$key}");
        }
    }

    private function cacheKey(string $key, ?string $dinasId): string
    {
        return 'policy:'.$key.':'.($dinasId ?? 'global');
    }
}
