<div class="space-y-6">
    <x-ui.page-header eyebrow="Program" title="Program Supervisi Tahunan"
        description="Susun rencana supervisi lintas guru binaan per tahun ajaran, lalu semai siklus secara massal.">
        <x-slot:actions>
            <x-ui.button wire:click="$toggle('showForm')">{{ $showForm ? 'Tutup' : 'Program baru' }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($showForm)
        <x-ui.card title="Program baru">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-ui.input label="Judul program" name="judul" wire:model="judul" placeholder="mis. Program Supervisi Klinis Semester Ganjil" />
                </div>
                <x-ui.input label="Tahun ajaran" name="tahun_ajaran" wire:model="tahun_ajaran" />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Semester</label>
                    <select wire:model="semester" class="field-input">
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>
                <div class="sm:col-span-2 space-y-1.5">
                    <label class="block text-sm font-medium">Catatan <span class="text-[var(--text-muted)]">(opsional)</span></label>
                    <textarea wire:model="catatan" rows="2" class="field-input"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <x-ui.button type="submit">Simpan &amp; kelola target</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    @forelse ($programs as $program)
        <a href="{{ route('programs.show', $program) }}" wire:navigate
           class="block rounded-xl border border-[var(--border)] bg-[var(--surface)] px-5 py-4 shadow-sm transition hover:border-brand-400">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-ink-900 dark:text-ink-50">{{ $program->judul }}</p>
                    <p class="text-sm text-[var(--text-muted)]">{{ $program->tahun_ajaran }} · {{ ucfirst($program->semester) }} · {{ $program->targets_count }} target</p>
                </div>
                <x-ui.status-badge :status="match ($program->status) { 'aktif' => 'progress', 'selesai' => 'done', 'dibatalkan' => 'canceled', default => 'draft' }" :label="ucfirst($program->status)" />
            </div>
        </a>
    @empty
        <x-ui.empty-state title="Belum ada program"
            description="Buat program tahunan untuk merencanakan siklus supervisi bagi seluruh guru binaan sekaligus." />
    @endforelse
</div>
