<div class="mx-auto max-w-2xl space-y-6">
    <x-ui.page-header title="Perencanaan Supervisi" :description="$cycle->judul" />

    <x-ui.card>
        <form wire:submit="save" class="space-y-4">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Fokus observasi</label>
                <textarea wire:model="fokusObservasi" rows="2" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] focus:ring-2 focus:ring-brand-600"></textarea>
                @error('fokusObservasi') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Tujuan (opsional)</label>
                <textarea wire:model="tujuan" rows="2" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Instrumen (Format A–E)</label>
                <select wire:model="instrumentVersionId" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]">
                    <option value="">Pilih instrumen…</option>
                    @foreach ($instruments as $instrument)
                        @foreach ($instrument->versions as $version)
                            <option value="{{ $version->id }}">{{ $instrument->nama }} — v{{ $version->version }}</option>
                        @endforeach
                    @endforeach
                </select>
                @error('instrumentVersionId') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Tipe observasi</label>
                    <select wire:model="tipeObservasi" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]">
                        <option value="sinkron">Sinkron</option>
                        <option value="asinkron">Asinkron</option>
                    </select>
                </div>
                <x-ui.input label="Jadwal mulai" name="jadwalMulai" type="datetime-local" wire:model="jadwalMulai" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input label="Kelas" name="kelas" wire:model="kelas" />
                <x-ui.input label="Mata pelajaran" name="mataPelajaran" wire:model="mataPelajaran" />
                <x-ui.input label="Lokasi" name="lokasi" wire:model="lokasi" />
            </div>

            <div class="flex flex-wrap gap-2 border-t border-[var(--border)] pt-4">
                <x-ui.button type="submit">Simpan kesepakatan</x-ui.button>
                @if ($agreement !== null && $cycle->status->value === 0)
                    <x-ui.button type="button" wire:click="consent" variant="secondary">Setujui</x-ui.button>
                @endif
                <x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost">Kembali</x-ui.button>
            </div>

            @if ($agreement !== null)
                <p class="text-xs text-[var(--text-muted)]">
                    Status kesepakatan:
                    {{ $agreement->disepakati_supervisor_at ? 'supervisor ✓' : 'supervisor ○' }},
                    {{ $agreement->disepakati_guru_at ? 'guru ✓' : 'guru ○' }}.
                    Mengubah fokus atau instrumen akan mereset persetujuan.
                </p>
            @endif
        </form>
    </x-ui.card>
</div>
