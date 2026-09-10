@php
    /** @var array<string, mixed> $s */
    $meta = $s['meta'] ?? [];
    $fmtDate = fn (?string $iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->translatedFormat('d F Y') : '—';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    body { font-size: 11px; color: #1f2937; line-height: 1.5; margin: 0; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    h2 { font-size: 12px; margin: 16px 0 4px; border-bottom: 1px solid #d1d5db; padding-bottom: 2px; }
    .muted { color: #6b7280; }
    .box { border: 1px solid #e5e7eb; padding: 6px 8px; margin-top: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #f3f4f6; font-size: 10px; }
    .tag { display: inline-block; background: #eef2ff; color: #3730a3; padding: 0 4px; font-size: 9px; }
    .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
    <h1>Laporan Supervisi Klinis</h1>
    <div class="muted">{{ $meta['judul'] ?? '' }}</div>

    <table style="margin-top:8px">
        <tr><th style="width:22%">Guru</th><td>{{ $meta['guru'] ?? '—' }}</td>
            <th style="width:22%">Supervisor</th><td>{{ $meta['supervisor'] ?? '—' }}</td></tr>
        <tr><th>Sekolah</th><td>{{ $meta['sekolah'] ?? '—' }}</td>
            <th>Tahun Ajaran</th><td>{{ ($meta['tahun_ajaran'] ?? '—') }} ({{ ucfirst($meta['semester'] ?? '') }})</td></tr>
        <tr><th>Disusun</th><td colspan="3">{{ $fmtDate($meta['disusun_pada'] ?? null) }}</td></tr>
    </table>

    @if (! empty($meta['catatan_override']))
        <div class="box"><strong>Catatan:</strong> {{ $meta['catatan_override'] }}</div>
    @endif

    <h2>1. Perencanaan</h2>
    @if (! empty($s['perencanaan']))
        <div class="box">
            Fokus observasi: {{ $s['perencanaan']['fokus'] ?? '—' }}<br>
            Instrumen: {{ $s['perencanaan']['instrumen'] ?? '—' }}<br>
            Jadwal: {{ $fmtDate($s['perencanaan']['jadwal'] ?? null) }}
        </div>
    @else <p class="muted">Tidak ada data perencanaan.</p> @endif

    <h2>2. Observasi &amp; 3. Analisis</h2>
    @if (! empty($s['analisis']))
        @php $skor = $s['analisis']['skor'] ?? []; @endphp
        <div class="box">
            Skor total:
            <strong>{{ isset($skor['total']) ? number_format((float) $skor['total'] * 100, 0).'%' : '—' }}</strong>
            @if (! empty($skor['band'])) <span class="tag">{{ $skor['band'] }}</span> @endif
            <span class="tag">sumber: {{ $s['analisis']['sumber'] ?? 'manual' }}</span>
        </div>
        <p>{{ $s['analisis']['ringkasan'] ?? '—' }}</p>
        @if (! empty($s['analisis']['temuan']))
            <table>
                <tr><th style="width:28%">Kategori</th><th>Temuan</th></tr>
                @foreach ($s['analisis']['temuan'] as $t)
                    <tr><td>{{ \Illuminate\Support\Str::headline($t['kategori'] ?? '') }}</td><td>{{ $t['deskripsi'] ?? '' }}</td></tr>
                @endforeach
            </table>
        @endif
    @else <p class="muted">Analisis belum final.</p> @endif

    <h2>4. Umpan Balik</h2>
    @if (! empty($s['umpan_balik']))
        <p>Dikonfirmasi guru: {{ ($s['umpan_balik']['dikonfirmasi_guru'] ?? false) ? 'Ya' : 'Belum' }}</p>
        @if (! empty($s['umpan_balik']['kesepakatan']))
            <ul>
                @foreach ($s['umpan_balik']['kesepakatan'] as $poin)
                    <li>{{ $poin }}</li>
                @endforeach
            </ul>
        @endif
    @else <p class="muted">Belum ada sesi umpan balik.</p> @endif

    <h2>5. Tindak Lanjut (RTL)</h2>
    @forelse ($s['tindak_lanjut'] ?? [] as $plan)
        <div class="box">
            <strong>{{ $plan['tujuan'] ?? '' }}</strong>
            <span class="tag">tenggat {{ $fmtDate($plan['tenggat'] ?? null) }}</span>
            <span class="tag">{{ $plan['status'] ?? '' }}</span>
            @if (! empty($plan['butir']))
                <table>
                    <tr><th>Butir</th><th style="width:18%">Status</th></tr>
                    @foreach ($plan['butir'] as $b)
                        <tr><td>{{ $b['deskripsi'] ?? '' }}</td><td>{{ $b['status'] ?? '' }}</td></tr>
                    @endforeach
                </table>
            @endif
        </div>
    @empty
        <p class="muted">Belum ada RTL.</p>
    @endforelse

    <h2>6. Riwayat Status</h2>
    <table>
        <tr><th>Dari</th><th>Ke</th><th>Oleh</th><th>Tanggal</th></tr>
        @foreach ($s['riwayat_status'] ?? [] as $r)
            <tr><td>{{ $r['dari'] ?? '' }}</td><td>{{ $r['ke'] ?? '' }}</td><td>{{ $r['oleh'] ?? '' }}</td><td>{{ $fmtDate($r['pada'] ?? null) }}</td></tr>
        @endforeach
    </table>

    <div class="footer">
        Dokumen ini dibangkitkan otomatis oleh Sistem E-Supervisi Klinis Pendidikan. Data pribadi guru — perlakukan sesuai UU 27/2022 tentang Pelindungan Data Pribadi.
    </div>
</body>
</html>
