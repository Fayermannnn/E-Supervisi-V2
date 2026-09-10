<div class="space-y-6">
    <x-ui.page-header eyebrow="Administrasi" title="Penugasan Supervisor" description="Tetapkan supervisor pembina untuk setiap guru.">
        <x-slot:actions><x-ui.button wire:click="create">Tambah penugasan</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-[var(--text-muted)]">
                    <tr><th class="px-4 py-3">Supervisor</th><th class="px-4 py-3">Guru</th><th class="px-4 py-3">Mulai</th><th class="px-4 py-3">Selesai</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($assignments as $a)
                        <tr wire:key="assign-{{ $a->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $a->supervisor?->name }}</div>
                                <div class="text-xs text-[var(--text-muted)]">{{ $a->supervisor?->supervisor_type?->label() }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $a->guru?->name }}</div>
                                <div class="text-xs text-[var(--text-muted)]">{{ $a->guru?->sekolah?->nama }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $a->mulai?->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($a->selesai)
                                    {{ $a->selesai->translatedFormat('d M Y') }}
                                @else
                                    <x-ui.status-badge status="done" label="Aktif" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @unless ($a->selesai)
                                    <button wire:click="end('{{ $a->id }}')" wire:confirm="Akhiri penugasan ini?" class="text-status-overdue">Akhiri</button>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-[var(--text-muted)]">Belum ada penugasan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-[var(--border)] p-4">{{ $assignments->links() }}</div>
    </x-ui.card>

    <x-app.slide-over wire-model="showForm" title="Tambah penugasan">
        <form wire:submit="save" class="space-y-4">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Supervisor</label>
                <select wire:model="supervisorId" class="field-input">
                    <option value="">Pilih supervisor…</option>
                    @foreach ($supervisors as $s) <option value="{{ $s->id }}">{{ $s->name }} — {{ $s->sekolah?->nama }}</option> @endforeach
                </select>
                @error('supervisorId') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Guru</label>
                <select wire:model="guruId" class="field-input">
                    <option value="">Pilih guru…</option>
                    @foreach ($gurus as $g) <option value="{{ $g->id }}">{{ $g->name }} — {{ $g->sekolah?->nama }}</option> @endforeach
                </select>
                @error('guruId') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>
            <x-ui.input label="Mulai" name="mulai" type="date" wire:model="mulai" required />
            <x-ui.input label="Selesai (opsional)" name="selesai" type="date" wire:model="selesai" />
            <x-ui.button type="submit">Simpan</x-ui.button>
        </form>
    </x-app.slide-over>
</div>
