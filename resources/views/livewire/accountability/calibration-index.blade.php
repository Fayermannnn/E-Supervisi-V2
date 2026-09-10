<div class="space-y-6">
    <x-ui.page-header eyebrow="Akuntabilitas" title="Kalibrasi Antar-Penilai"
        description="Uji konsistensi penilaian antar pengawas / kepala sekolah pada artefak observasi yang sama — mendukung reliabilitas instrumen.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button wire:click="$toggle('showForm')">{{ $showForm ? 'Tutup' : 'Sesi baru' }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @error('judul') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($showForm && $canManage)
        <x-ui.card title="Sesi kalibrasi baru">
            <form wire:submit="create" class="space-y-4">
                <x-ui.input label="Judul" name="judul" wire:model="judul" />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Instrumen</label>
                    <select wire:model="instrument_version_id" class="field-input">
                        <option value="">— pilih —</option>
                        @foreach ($instruments as $instrument)
                            @foreach ($instrument->versions as $version)
                                <option value="{{ $version->id }}">{{ $instrument->nama }} v{{ $version->version }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('instrument_version_id') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                </div>
                <x-ui.input label="Tautan artefak (rekaman/dokumen yang dinilai bersama)" name="artefak_url" wire:model="artefak_url" />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Deskripsi <span class="text-[var(--text-muted)]">(opsional)</span></label>
                    <textarea wire:model="deskripsi" rows="2" class="field-input"></textarea>
                </div>
                <x-ui.button type="submit">Buat sesi</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    @forelse ($sessions as $session)
        <a href="{{ route('calibration.show', $session) }}" wire:navigate
           class="block rounded-xl border border-[var(--border)] bg-[var(--surface)] px-5 py-4 shadow-sm transition hover:border-brand-400">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-ink-900 dark:text-ink-50">{{ $session->judul }}</p>
                    <p class="text-sm text-[var(--text-muted)]">{{ $session->participants_count }} penilai</p>
                </div>
                <div class="flex items-center gap-3">
                    @if ($session->status === 'selesai' && $session->stats)
                        <span class="text-xs text-[var(--text-muted)]">Kesepakatan {{ number_format(($session->stats['persen_kesepakatan'] ?? 0) * 100, 0) }}%</span>
                    @endif
                    <x-ui.status-badge :status="match ($session->status) { 'selesai' => 'done', 'berjalan' => 'progress', default => 'draft' }" :label="ucfirst($session->status)" />
                </div>
            </div>
        </a>
    @empty
        <x-ui.empty-state title="Belum ada sesi kalibrasi" />
    @endforelse
</div>
