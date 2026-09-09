<div class="mx-auto max-w-2xl space-y-6">
    <x-ui.page-header title="Buat Siklus Supervisi" description="Pilih guru binaan dan tetapkan konteks siklus." />

    <x-ui.card>
        <form wire:submit="save" class="space-y-4">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Guru binaan</label>
                <select wire:model="guruId" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] focus:ring-2 focus:ring-brand-600">
                    <option value="">Pilih guru…</option>
                    @foreach ($binaan as $g)
                        <option value="{{ $g->id }}">{{ $g->name }} — {{ $g->sekolah?->nama }}</option>
                    @endforeach
                </select>
                @error('guruId') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                @if ($binaan->isEmpty())
                    <p class="text-xs text-[var(--text-muted)]">Belum ada guru binaan aktif. Hubungi Admin untuk penugasan.</p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input label="Tahun ajaran" name="tahunAjaran" wire:model="tahunAjaran" required />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Semester</label>
                    <select wire:model="semester" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]">
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>
            </div>

            <x-ui.input label="Judul siklus" name="judul" wire:model="judul" required placeholder="mis. Supervisi Pembelajaran Matematika Kelas VIII" />

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Fokus ringkas (opsional)</label>
                <textarea wire:model="fokusRingkas" rows="3" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] focus:ring-2 focus:ring-brand-600"></textarea>
            </div>

            <div class="flex gap-2 pt-2">
                <x-ui.button type="submit">Buat & lanjut ke perencanaan</x-ui.button>
                <x-ui.button as="a" href="{{ route('cycles.index') }}" variant="ghost">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
