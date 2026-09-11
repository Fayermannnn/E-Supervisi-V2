<div class="space-y-6">
    <x-ui.page-header eyebrow="Pelaporan" title="Pelaporan Agregat" description="Ringkasan lintas sekolah di dinas Anda. Data individual guru tidak ditampilkan.">
        <x-slot:actions>
            <x-ui.button variant="secondary" wire:click="export('pdf')">PDF</x-ui.button>
            <x-ui.button variant="secondary" wire:click="export('xlsx')">XLSX</x-ui.button>
            <x-ui.button variant="ghost" wire:click="export('csv')">CSV</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('export') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($exports->isNotEmpty())
        <x-ui.card title="Berkas ekspor" wire:poll.5s>
            <ul class="divide-y divide-[var(--border)] text-sm">
                @foreach ($exports->take(6) as $ex)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ strtoupper($ex->format) }} <span class="text-xs text-[var(--text-muted)]">· {{ $ex->created_at->diffForHumans() }}</span></span>
                        @if ($ex->isReady())
                            <a href="{{ route('reports.exports.download', $ex) }}" class="text-brand-700 hover:underline">Unduh</a>
                        @elseif ($ex->status === 'gagal')
                            <span class="text-xs text-status-overdue">Gagal</span>
                        @else
                            <span class="text-xs text-[var(--text-muted)]">{{ ucfirst($ex->status) }}…</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    <x-ui.card>
        <div class="flex flex-wrap gap-3">
            <select wire:model.live="tahunAjaran" class="field-input">
                <option value="">Semua tahun ajaran</option>
                @foreach ($tahunOptions as $t) <option value="{{ $t }}">{{ $t }}</option> @endforeach
            </select>
            <select wire:model.live="wilayah" class="field-input">
                <option value="">Semua wilayah</option>
                @foreach ($wilayahOptions as $w) <option value="{{ $w }}">{{ $w }}</option> @endforeach
            </select>
            <select wire:model.live="jenjang" class="field-input">
                <option value="">Semua jenjang</option>
                @foreach (['SD','SMP','SMA','SMK'] as $j) <option value="{{ $j }}">{{ $j }}</option> @endforeach
            </select>
        </div>
    </x-ui.card>

    <dl class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat label="Total siklus" :value="$data['total_siklus']" />
        <x-ui.stat label="Dilaporkan" :value="$data['per_status']['Dilaporkan'] ?? 0" tone="success" />
        <x-ui.stat label="RTL terlambat" :value="$data['rtl_per_status']['terlambat'] ?? 0" tone="danger" />
    </dl>

    @php
        $statusSegments = collect(\App\Support\Enums\CycleStatus::cases())
            ->map(fn ($st) => ['label' => $st->label(), 'value' => (int) ($data['per_status'][$st->label()] ?? 0), 'tone' => $st->tone()])
            ->all();

        $rtlToneMap = ['selesai' => 'done', 'berjalan' => 'progress', 'terlambat' => 'overdue', 'dibatalkan' => 'archived'];
        $rtlSegments = collect($data['rtl_per_status'])
            ->map(fn ($v, $k) => ['label' => \Illuminate\Support\Str::title((string) $k), 'value' => (int) $v, 'tone' => $rtlToneMap[$k] ?? 'neutral'])
            ->values()->all();
    @endphp

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Siklus per status" :subtitle="$data['total_siklus'].' siklus total'">
            <x-ui.bar-distribution :segments="$statusSegments" unit="siklus" />
        </x-ui.card>

        <x-ui.card title="Tindak lanjut per status">
            @if (collect($rtlSegments)->sum('value') > 0)
                <x-ui.bar-distribution :segments="$rtlSegments" unit="RTL" />
            @else
                <p class="text-sm text-[var(--text-muted)]">Belum ada rencana tindak lanjut.</p>
            @endif
        </x-ui.card>
    </div>

    @if (! empty($data['anomali']))
        <x-ui.card title="Anomali untuk tinjauan">
            <x-ui.ai-draft-banner>Ditandai otomatis. Sistem tidak mengambil tindakan — verifikasi manual.</x-ui.ai-draft-banner>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-status-progress">
                @foreach ($data['anomali'] as $a) <li>{{ $a }}</li> @endforeach
            </ul>
        </x-ui.card>
    @endif

    <x-ui.card title="Per sekolah" flush>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-[var(--text-muted)]">
                    <tr><th class="px-4 py-3">Sekolah</th><th class="px-4 py-3">Wilayah</th><th class="px-4 py-3">Total</th><th class="px-4 py-3 min-w-[10rem]">Dilaporkan</th><th class="px-4 py-3">RTL terlambat</th></tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($data['per_sekolah'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $row['sekolah'] }}</td>
                            <td class="px-4 py-3">{{ $row['wilayah'] ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $row['total'] }}</td>
                            <td class="px-4 py-3">
                                <x-ui.meter
                                    :value="$row['dilaporkan']" :max="max(1, $row['total'])"
                                    tone="done"
                                    :value-label="$row['dilaporkan'].'/'.$row['total']" />
                            </td>
                            <td class="px-4 py-3 tabular-nums {{ $row['tindak_lanjut_terlambat'] > 0 ? 'text-status-overdue font-medium' : '' }}">{{ $row['tindak_lanjut_terlambat'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-[var(--text-muted)]">Tidak ada data untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
