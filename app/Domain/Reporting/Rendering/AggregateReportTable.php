<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Rendering;

/**
 * Mengubah snapshot laporan agregat (`BuildAggregateReport`) menjadi bentuk
 * tabular untuk ekspor XLSX/CSV/PDF. Deterministik & bebas efek samping —
 * diuji unit.
 */
final class AggregateReportTable
{
    public const HEADERS_SEKOLAH = ['Sekolah', 'Wilayah', 'Total siklus', 'Dilaporkan', 'RTL terlambat'];

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     ringkasan: list<array{label: string, nilai: int|string}>,
     *     per_sekolah: array{headers: list<string>, rows: list<list<int|string>>},
     *     rtl_per_status: list<array{label: string, nilai: int}>,
     *     anomali: list<string>
     * }
     */
    public static function fromSnapshot(array $data): array
    {
        $perStatus = is_array($data['per_status'] ?? null) ? $data['per_status'] : [];
        $ringkasan = [['label' => 'Total siklus', 'nilai' => (int) ($data['total_siklus'] ?? 0)]];
        foreach ($perStatus as $label => $count) {
            $ringkasan[] = ['label' => (string) $label, 'nilai' => (int) $count];
        }

        $rows = [];
        foreach (is_array($data['per_sekolah'] ?? null) ? $data['per_sekolah'] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rows[] = [
                (string) ($row['sekolah'] ?? '—'),
                (string) ($row['wilayah'] ?? '—'),
                (int) ($row['total'] ?? 0),
                (int) ($row['dilaporkan'] ?? 0),
                (int) ($row['tindak_lanjut_terlambat'] ?? 0),
            ];
        }

        $rtl = [];
        foreach (is_array($data['rtl_per_status'] ?? null) ? $data['rtl_per_status'] : [] as $label => $count) {
            $rtl[] = ['label' => (string) $label, 'nilai' => (int) $count];
        }

        return [
            'ringkasan' => $ringkasan,
            'per_sekolah' => ['headers' => self::HEADERS_SEKOLAH, 'rows' => $rows],
            'rtl_per_status' => $rtl,
            'anomali' => array_values(array_map('strval', is_array($data['anomali'] ?? null) ? $data['anomali'] : [])),
        ];
    }
}
