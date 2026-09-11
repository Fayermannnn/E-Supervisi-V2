<div class="mx-auto max-w-3xl space-y-6">
    <x-ui.page-header eyebrow="Siklus" title="Laporan Siklus" :description="$cycle->judul">
        <x-slot:actions>
            @if ($snapshot && $canExport)
                <x-ui.button variant="secondary" wire:click="requestExport">Unduh PDF</x-ui.button>
            @endif
            @if ($snapshot)
                <x-ui.button variant="ghost" x-on:click="window.print()">Cetak</x-ui.button>
            @endif
            <x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost">Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('report') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($exports->isNotEmpty())
        <x-ui.card title="Berkas ekspor" wire:poll.5s>
            <ul class="divide-y divide-[var(--border)] text-sm">
                @foreach ($exports as $ex)
                    <li class="flex items-center justify-between py-2">
                        <span>
                            {{ strtoupper($ex->format) }}
                            <span class="text-xs text-[var(--text-muted)]">· {{ $ex->created_at->diffForHumans() }}</span>
                        </span>
                        @if ($ex->isReady())
                            <a href="{{ route('reports.exports.download', $ex) }}" class="text-brand-700 hover:underline">Unduh ({{ number_format(($ex->ukuran ?? 0) / 1024, 0) }} KB)</a>
                        @elseif ($ex->status === 'gagal')
                            <span class="text-xs text-status-overdue">Gagal: {{ \Illuminate\Support\Str::limit($ex->error, 80) }}</span>
                        @else
                            <span class="text-xs text-[var(--text-muted)]">{{ ucfirst($ex->status) }}…</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    @if (! $snapshot && $canCompile)
        <x-ui.card title="Susun laporan">
            <p class="text-sm text-[var(--text-muted)]">Laporan memuat ringkasan enam tahap siklus. Bila masih ada RTL terbuka, beri catatan.</p>
            <div class="mt-3 space-y-3">
                <textarea wire:model="overrideNote" rows="2" placeholder="Catatan (opsional, wajib bila RTL belum selesai)…"
                    class="field-input"></textarea>
                <x-ui.button wire:click="compile">Susun laporan final</x-ui.button>
            </div>
        </x-ui.card>
    @elseif (! $snapshot)
        <x-ui.empty-state title="Laporan belum disusun" description="Supervisor menyusun laporan setelah tindak lanjut berjalan." />
    @else
        <x-ui.card>
            <article class="prose prose-sm max-w-none dark:prose-invert">
                <h1>Laporan Supervisi Klinis</h1>
                <p><strong>{{ $snapshot['meta']['judul'] }}</strong><br>
                    Guru: {{ $snapshot['meta']['guru'] }} · Supervisor: {{ $snapshot['meta']['supervisor'] }}<br>
                    {{ $snapshot['meta']['sekolah'] }} · {{ $snapshot['meta']['tahun_ajaran'] }} ({{ ucfirst($snapshot['meta']['semester']) }})</p>

                @if ($snapshot['meta']['catatan_override'])
                    <p><em>Catatan: {{ $snapshot['meta']['catatan_override'] }}</em></p>
                @endif

                <h2>1. Perencanaan</h2>
                @if ($snapshot['perencanaan'])
                    <p>Fokus: {{ $snapshot['perencanaan']['fokus'] }}<br>Instrumen: {{ $snapshot['perencanaan']['instrumen'] }}</p>
                @else <p>—</p> @endif

                <h2>2–3. Analisis</h2>
                @if ($snapshot['analisis'])
                    @php $skorTotal = $snapshot['analisis']['skor']['total'] ?? null; @endphp
                    <p>Skor total: {{ $skorTotal !== null ? number_format($skorTotal * 100, 0).'%' : '—' }}
                        ({{ $snapshot['analisis']['skor']['band'] ?? '—' }})</p>
                    @if ($skorTotal !== null)
                        <div class="not-prose max-w-xs">
                            <x-ui.meter :value="$skorTotal" :value-label="number_format($skorTotal * 100, 0).'%'" />
                        </div>
                        @if (! empty($snapshot['analisis']['skor']['sections']))
                            <div class="not-prose mt-3 max-w-sm space-y-2">
                                @foreach ($snapshot['analisis']['skor']['sections'] as $key => $sec)
                                    <x-ui.meter
                                        :label="\Illuminate\Support\Str::headline((string) $key)"
                                        :value="$sec['score'] ?? 0"
                                        :value-label="($sec['score'] ?? null) !== null ? number_format($sec['score'] * 100, 0).'%' : '—'" />
                                @endforeach
                            </div>
                        @endif
                    @endif
                    <p style="white-space: pre-line">{{ $snapshot['analisis']['ringkasan'] }}</p>
                    <ul>
                        @foreach ($snapshot['analisis']['temuan'] ?? [] as $t)
                            <li><strong>{{ $t['kategori'] }}:</strong> {{ $t['deskripsi'] }}</li>
                        @endforeach
                    </ul>
                @else <p>—</p> @endif

                <h2>4. Umpan Balik</h2>
                @if ($snapshot['umpan_balik'])
                    <p>Dikonfirmasi guru: {{ $snapshot['umpan_balik']['dikonfirmasi_guru'] ? 'Ya' : 'Belum' }}</p>
                    <ul>@foreach ($snapshot['umpan_balik']['kesepakatan'] ?? [] as $k)<li>{{ $k }}</li>@endforeach</ul>
                @else <p>—</p> @endif

                <h2>5. Tindak Lanjut</h2>
                @forelse ($snapshot['tindak_lanjut'] ?? [] as $p)
                    <p><strong>{{ $p['tujuan'] }}</strong> — tenggat {{ $p['tenggat'] }} — status: {{ $p['status'] }}</p>
                    <ul>@foreach ($p['butir'] as $b)<li>{{ $b['deskripsi'] }} ({{ $b['status'] }})</li>@endforeach</ul>
                @empty <p>—</p> @endforelse

                <h2>Riwayat Status</h2>
                <ul>
                    @foreach ($snapshot['riwayat_status'] ?? [] as $r)
                        <li>{{ $r['dari'] }} → {{ $r['ke'] }} — {{ $r['oleh'] }}</li>
                    @endforeach
                </ul>
                <p><small>Disusun {{ \Illuminate\Support\Carbon::parse($snapshot['meta']['disusun_pada'])->translatedFormat('d M Y H:i') }}</small></p>
            </article>
        </x-ui.card>
    @endif
</div>
