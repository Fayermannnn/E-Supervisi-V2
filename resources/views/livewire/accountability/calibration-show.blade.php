<div class="space-y-6">
    <x-ui.page-header eyebrow="Akuntabilitas" :title="$session->judul" :description="$session->deskripsi">
        <x-slot:actions>
            <x-ui.status-badge :status="match ($session->status) { 'selesai' => 'done', 'berjalan' => 'progress', default => 'draft' }" :label="ucfirst($session->status)" />
            <x-ui.button as="a" href="{{ route('calibration.index') }}" variant="ghost" wire:navigate>Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($session->artefak_url)
        <x-ui.alert variant="info">Artefak yang dinilai: <a href="{{ $session->artefak_url }}" target="_blank" rel="noopener" class="underline">{{ $session->artefak_url }}</a></x-ui.alert>
    @endif

    @error('scores') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror
    @error('addSupervisorId') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    <x-ui.card title="Penilai">
        <ul class="divide-y divide-[var(--border)] text-sm">
            @forelse ($participants as $p)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $p->supervisor?->name }}</span>
                    <x-ui.status-badge :status="$p->submitted_at ? 'done' : 'draft'" :label="$p->submitted_at ? 'Terkirim' : 'Menunggu'" />
                </li>
            @empty
                <li class="py-2 text-[var(--text-muted)]">Belum ada penilai.</li>
            @endforelse
        </ul>

        @if ($canManage && $session->status !== 'selesai')
            <form wire:submit="addParticipant" class="mt-4 flex flex-wrap items-end gap-2 border-t border-[var(--border)] pt-4">
                <div class="flex-1 space-y-1.5">
                    <label class="block text-sm font-medium">Tambah penilai</label>
                    <select wire:model="addSupervisorId" class="field-input">
                        <option value="">— pilih supervisor —</option>
                        @foreach ($candidates as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.button type="submit" variant="secondary">Tambah</x-ui.button>
            </form>
        @endif
    </x-ui.card>

    @if ($isParticipant && $session->status === 'berjalan')
        <x-ui.card title="Skor Anda" subtitle="Isi skor untuk setiap butir sesuai penilaian independen Anda atas artefak.">
            <form wire:submit="submitScores" class="space-y-3">
                @foreach ($items as $item)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm">{{ $item['label'] }} <span class="text-xs text-[var(--text-muted)]">({{ $item['section'] }})</span></span>
                        <input type="number" step="0.5" min="0" wire:model="scores.{{ $item['key'] }}"
                               class="field-input w-24 py-1">
                    </div>
                @endforeach
                <x-ui.button type="submit">Kirim skor</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    @if ($canManage && $session->status === 'berjalan')
        <x-ui.button wire:click="close" wire:confirm="Tutup sesi dan hitung statistik? Butuh minimal 2 penilai yang sudah mengirim.">Tutup sesi &amp; hitung</x-ui.button>
    @endif

    @if ($session->status === 'selesai' && $session->stats)
        @php $s = $session->stats; @endphp
        <x-ui.card title="Hasil reliabilitas">
            <dl class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-[var(--border)] bg-[var(--surface-muted)]/50 px-3.5 py-2.5">
                    <dt class="text-xs text-[var(--text-muted)]">Persen kesepakatan</dt>
                    <dd class="text-lg font-semibold">{{ $s['persen_kesepakatan'] !== null ? number_format($s['persen_kesepakatan'] * 100, 0).'%' : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-[var(--border)] bg-[var(--surface-muted)]/50 px-3.5 py-2.5">
                    <dt class="text-xs text-[var(--text-muted)]">Fleiss' κ</dt>
                    <dd class="text-lg font-semibold">{{ $s['fleiss_kappa'] ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-[var(--border)] bg-[var(--surface-muted)]/50 px-3.5 py-2.5">
                    <dt class="text-xs text-[var(--text-muted)]">Deviasi absolut rata-rata</dt>
                    <dd class="text-lg font-semibold">{{ $s['deviasi_absolut_rata'] ?? '—' }}</dd>
                </div>
            </dl>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-[var(--border)] text-left text-xs text-[var(--text-muted)]">
                        <tr><th class="py-2 pr-3">Butir</th><th class="px-3 py-2">Mean</th><th class="px-3 py-2">Variansi</th><th class="px-3 py-2">Rentang</th><th class="px-3 py-2">Kesepakatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @foreach ($s['per_item'] ?? [] as $key => $row)
                            <tr>
                                <td class="py-2 pr-3">{{ $key }}</td>
                                <td class="px-3 py-2">{{ $row['mean'] }}</td>
                                <td class="px-3 py-2">{{ $row['variance'] }}</td>
                                <td class="px-3 py-2">{{ $row['range'] }}</td>
                                <td class="px-3 py-2">{{ number_format($row['kesepakatan'] * 100, 0) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif
</div>
