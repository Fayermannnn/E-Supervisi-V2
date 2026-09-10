<div class="space-y-6">
    <x-ui.page-header title="Pelaporan Agregat" description="Ringkasan lintas sekolah di dinas Anda. Data individual guru tidak ditampilkan." />

    <x-ui.card>
        <div class="flex flex-wrap gap-3">
            <select wire:model.live="tahunAjaran" class="rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]">
                <option value="">Semua tahun ajaran</option>
                @foreach ($tahunOptions as $t) <option value="{{ $t }}">{{ $t }}</option> @endforeach
            </select>
            <select wire:model.live="wilayah" class="rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]">
                <option value="">Semua wilayah</option>
                @foreach ($wilayahOptions as $w) <option value="{{ $w }}">{{ $w }}</option> @endforeach
            </select>
            <select wire:model.live="jenjang" class="rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]">
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
                    <tr><th class="px-4 py-3">Sekolah</th><th class="px-4 py-3">Wilayah</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Dilaporkan</th><th class="px-4 py-3">RTL terlambat</th></tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($data['per_sekolah'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $row['sekolah'] }}</td>
                            <td class="px-4 py-3">{{ $row['wilayah'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row['total'] }}</td>
                            <td class="px-4 py-3">{{ $row['dilaporkan'] }}</td>
                            <td class="px-4 py-3 {{ $row['tindak_lanjut_terlambat'] > 0 ? 'text-status-overdue font-medium' : '' }}">{{ $row['tindak_lanjut_terlambat'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-[var(--text-muted)]">Tidak ada data untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
