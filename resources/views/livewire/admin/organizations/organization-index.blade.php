<div class="space-y-6">
    <x-ui.page-header eyebrow="Administrasi" title="Struktur Organisasi" description="Kelola dinas pendidikan dan sekolah di bawahnya." />

    <div class="flex gap-1 border-b border-[var(--border)]">
        <button wire:click="$set('tab', 'sekolah')" class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'sekolah' ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500' }}">Sekolah</button>
        @if ($canManageDinas)
            <button wire:click="$set('tab', 'dinas')" class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'dinas' ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500' }}">Dinas</button>
        @endif
    </div>

    @if ($tab === 'dinas' && $canManageDinas)
        <x-ui.card flush>
            <div class="flex justify-end border-b border-[var(--border)] p-4">
                <x-ui.button wire:click="newDinas">Tambah dinas</x-ui.button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-[var(--text-muted)]">
                        <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Tipe</th><th class="px-4 py-3">Provinsi</th><th class="px-4 py-3">Sekolah</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @foreach ($dinasList as $d)
                            <tr wire:key="dinas-{{ $d->id }}">
                                <td class="px-4 py-3 font-medium">{{ $d->nama }}</td>
                                <td class="px-4 py-3">{{ $d->kode }}</td>
                                <td class="px-4 py-3 capitalize">{{ $d->tipe }}</td>
                                <td class="px-4 py-3">{{ $d->provinsi }}</td>
                                <td class="px-4 py-3">{{ $d->sekolah_count }}</td>
                                <td class="px-4 py-3 text-right"><button wire:click="editDinas('{{ $d->id }}')" class="text-brand-600">Ubah</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @else
        <x-ui.card flush>
            <div class="flex justify-end border-b border-[var(--border)] p-4">
                <x-ui.button wire:click="newSekolah">Tambah sekolah</x-ui.button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-[var(--text-muted)]">
                        <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">NPSN</th><th class="px-4 py-3">Jenjang</th><th class="px-4 py-3">Dinas</th><th class="px-4 py-3">Wilayah</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse ($sekolahList as $s)
                            <tr wire:key="sekolah-{{ $s->id }}">
                                <td class="px-4 py-3 font-medium">{{ $s->nama }}</td>
                                <td class="px-4 py-3">{{ $s->npsn ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $s->jenjang }}</td>
                                <td class="px-4 py-3">{{ $s->dinas?->nama }}</td>
                                <td class="px-4 py-3">{{ $s->wilayah ?? '—' }}</td>
                                <td class="px-4 py-3 text-right"><button wire:click="editSekolah('{{ $s->id }}')" class="text-brand-600">Ubah</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-[var(--text-muted)]">Belum ada sekolah.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[var(--border)] p-4">{{ $sekolahList->links() }}</div>
        </x-ui.card>
    @endif

    <x-app.slide-over wire-model="showDinasForm" title="{{ $editingDinasId ? 'Ubah dinas' : 'Tambah dinas' }}">
        <form wire:submit="saveDinas" class="space-y-4">
            <x-ui.input label="Nama dinas" name="dinasNama" wire:model="dinasNama" required />
            <x-ui.input label="Kode" name="dinasKode" wire:model="dinasKode" required />
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Tipe</label>
                <select wire:model="dinasTipe" class="field-input">
                    <option value="kabupaten">Kabupaten</option><option value="kota">Kota</option>
                </select>
            </div>
            <x-ui.input label="Provinsi" name="dinasProvinsi" wire:model="dinasProvinsi" required />
            <x-ui.button type="submit">Simpan</x-ui.button>
        </form>
    </x-app.slide-over>

    <x-app.slide-over wire-model="showSekolahForm" title="{{ $editingSekolahId ? 'Ubah sekolah' : 'Tambah sekolah' }}">
        <form wire:submit="saveSekolah" class="space-y-4">
            <x-ui.input label="Nama sekolah" name="sekolahNama" wire:model="sekolahNama" required />
            <x-ui.input label="NPSN" name="sekolahNpsn" wire:model="sekolahNpsn" />
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Jenjang</label>
                <select wire:model="sekolahJenjang" class="field-input">
                    @foreach (['PAUD','SD','SMP','SMA','SMK','SLB'] as $j) <option value="{{ $j }}">{{ $j }}</option> @endforeach
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Dinas</label>
                <select wire:model="sekolahDinasId" name="sekolahDinasId" class="field-input">
                    <option value="">Pilih dinas…</option>
                    @foreach ($dinasOptions as $d) <option value="{{ $d->id }}">{{ $d->nama }}</option> @endforeach
                </select>
                @error('sekolahDinasId') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>
            <x-ui.input label="Kecamatan" name="sekolahKecamatan" wire:model="sekolahKecamatan" />
            <x-ui.input label="Wilayah (mis. Kota / 3T)" name="sekolahWilayah" wire:model="sekolahWilayah" />
            <x-ui.button type="submit">Simpan</x-ui.button>
        </form>
    </x-app.slide-over>
</div>
