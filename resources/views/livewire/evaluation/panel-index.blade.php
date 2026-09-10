<div class="space-y-6">
    <x-ui.page-header title="Evaluasi Ahli"
        description="Validasi artefak sistem oleh panel ahli (manajemen pendidikan &amp; sistem informasi) — mendukung DSR Artikel 3.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button wire:click="$toggle('showForm')">{{ $showForm ? 'Tutup' : 'Panel baru' }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @error('judul') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($showForm && $canManage)
        <x-ui.card title="Panel evaluasi baru">
            <form wire:submit="create" class="space-y-4">
                <x-ui.input label="Judul panel" name="judul" wire:model="judul" placeholder="mis. Validasi Artefak E-Supervisi v2.0" />
                <x-ui.input label="Versi artefak yang dievaluasi" name="artefak_versi" wire:model="artefak_versi" />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Deskripsi / instruksi untuk ahli <span class="text-[var(--text-muted)]">(opsional)</span></label>
                    <textarea wire:model="deskripsi" rows="3" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
                </div>
                <x-ui.button type="submit">Buat panel</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    @forelse ($panels as $panel)
        @php $mine = $myReviews->get($panel->id); @endphp
        <a href="{{ route($canManage ? 'evaluation.show' : 'evaluation.review', $panel) }}" wire:navigate
           class="block rounded-xl border border-[var(--border)] bg-[var(--surface)] px-5 py-4 shadow-sm transition hover:border-brand-400">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-ink-900 dark:text-ink-50">{{ $panel->judul }}</p>
                    <p class="text-sm text-[var(--text-muted)]">
                        {{ $panel->artefak_versi }}
                        @if ($canManage) · {{ $panel->experts_count }} ahli @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @if (! $canManage && $mine)
                        <x-ui.status-badge :status="$mine->review && $mine->review->isSubmitted() ? 'done' : 'draft'"
                            :label="$mine->review && $mine->review->isSubmitted() ? 'Sudah dinilai' : 'Perlu dinilai'" />
                    @endif
                    <x-ui.status-badge :status="match ($panel->status) { 'selesai' => 'done', 'berjalan' => 'progress', default => 'draft' }" :label="ucfirst($panel->status)" />
                </div>
            </div>
        </a>
    @empty
        <x-ui.empty-state title="Belum ada panel evaluasi"
            :description="$canManage ? 'Buat panel dan tugaskan ahli untuk menilai artefak.' : 'Anda belum ditugaskan pada panel evaluasi mana pun.'" />
    @endforelse
</div>
