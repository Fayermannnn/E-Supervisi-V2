@php
    /** @var array{ringkasan: array, per_sekolah: array, rtl_per_status: array, anomali: array} $table */
    /** @var array{digenerate_pada: string, filter: array} $meta */
    $gen = \Illuminate\Support\Carbon::parse($meta['digenerate_pada'])->translatedFormat('d F Y H:i');
    $filter = array_filter($meta['filter'] ?? []);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    body { font-size: 11px; color: #1f2937; margin: 0; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    h2 { font-size: 12px; margin: 14px 0 4px; }
    .muted { color: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
    th { background: #f3f4f6; font-size: 10px; }
    td.num, th.num { text-align: right; }
    .footer { margin-top: 18px; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
    <h1>Laporan Agregat Supervisi — Dinas</h1>
    <div class="muted">
        Digenerate {{ $gen }}
        @if ($filter) · Filter: {{ collect($filter)->map(fn ($v, $k) => "$k=$v")->join(', ') }} @endif
    </div>

    <h2>Ringkasan</h2>
    <table>
        <tr><th>Indikator</th><th class="num">Nilai</th></tr>
        @foreach ($table['ringkasan'] as $r)
            <tr><td>{{ $r['label'] }}</td><td class="num">{{ $r['nilai'] }}</td></tr>
        @endforeach
    </table>

    <h2>Per Sekolah</h2>
    <table>
        <tr>
            @foreach ($table['per_sekolah']['headers'] as $h)
                <th class="{{ $loop->index >= 2 ? 'num' : '' }}">{{ $h }}</th>
            @endforeach
        </tr>
        @forelse ($table['per_sekolah']['rows'] as $row)
            <tr>
                @foreach ($row as $cell)
                    <td class="{{ $loop->index >= 2 ? 'num' : '' }}">{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($table['per_sekolah']['headers']) }}" class="muted">Tidak ada data.</td></tr>
        @endforelse
    </table>

    @if (! empty($table['rtl_per_status']))
        <h2>RTL per Status</h2>
        <table>
            <tr><th>Status</th><th class="num">Jumlah</th></tr>
            @foreach ($table['rtl_per_status'] as $r)
                <tr><td>{{ $r['label'] }}</td><td class="num">{{ $r['nilai'] }}</td></tr>
            @endforeach
        </table>
    @endif

    @if (! empty($table['anomali']))
        <h2>Anomali untuk Ditinjau</h2>
        <ul>
            @foreach ($table['anomali'] as $a)
                <li>{{ $a }}</li>
            @endforeach
        </ul>
    @endif

    <div class="footer">
        Laporan agregat tidak memuat data individual guru di luar kebijakan akses. Dibangkitkan otomatis oleh Sistem E-Supervisi Klinis Pendidikan.
    </div>
</body>
</html>
