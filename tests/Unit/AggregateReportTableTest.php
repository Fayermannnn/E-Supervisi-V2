<?php

declare(strict_types=1);

use App\Domain\Reporting\Rendering\AggregateReportTable;

it('flattens an aggregate snapshot into ringkasan + per-sekolah rows', function () {
    $snapshot = [
        'total_siklus' => 12,
        'per_status' => ['Draf' => 3, 'Dilaporkan' => 5, 'Diarsipkan' => 4],
        'per_sekolah' => [
            ['sekolah' => 'SMP Negeri 1', 'wilayah' => '3T', 'total' => 7, 'dilaporkan' => 4, 'tindak_lanjut_terlambat' => 1],
            ['sekolah' => 'SMA Negeri 2', 'wilayah' => 'Kota', 'total' => 5, 'dilaporkan' => 1, 'tindak_lanjut_terlambat' => 0],
        ],
        'rtl_per_status' => ['berjalan' => 6, 'terlambat' => 1],
        'anomali' => ['SMA Negeri 2: 5 siklus, belum ada yang dilaporkan.'],
    ];

    $table = AggregateReportTable::fromSnapshot($snapshot);

    expect($table['ringkasan'][0])->toBe(['label' => 'Total siklus', 'nilai' => 12])
        ->and($table['ringkasan'])->toContain(['label' => 'Dilaporkan', 'nilai' => 5])
        ->and($table['per_sekolah']['headers'])->toBe(AggregateReportTable::HEADERS_SEKOLAH)
        ->and($table['per_sekolah']['rows'])->toHaveCount(2)
        ->and($table['per_sekolah']['rows'][0])->toBe(['SMP Negeri 1', '3T', 7, 4, 1])
        ->and($table['rtl_per_status'])->toContain(['label' => 'terlambat', 'nilai' => 1])
        ->and($table['anomali'])->toHaveCount(1);
});

it('tolerates a bare / empty snapshot', function () {
    $table = AggregateReportTable::fromSnapshot([]);

    expect($table['ringkasan'])->toBe([['label' => 'Total siklus', 'nilai' => 0]])
        ->and($table['per_sekolah']['rows'])->toBe([])
        ->and($table['anomali'])->toBe([]);
});
