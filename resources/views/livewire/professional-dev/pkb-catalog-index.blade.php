<div class="space-y-6">
    <x-ui.page-header eyebrow="Pengembangan Profesional" title="Katalog PKB"
        description="Materi & kegiatan pengembangan keprofesian berkelanjutan yang dapat direkomendasikan dari hasil analisis siklus.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button wire:click="$toggle('showForm')">{{ $showForm ? 'Tutup' : 'Item baru' }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @error('judul') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($showForm && $canManage)
        <x-ui.card :title="$editingId ? 'Sunting item' : 'Item baru'">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-ui.input label="Judul" name="judul" wire:model="judul" /></div>
                <div class="sm:col-span-2 space-y-1.5">
                    <label class="block text-sm font-medium">Deskripsi</label>
                    <textarea wire:model="deskripsi" rows="3" class="field-input"></textarea>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Tipe</label>
                    <select wire:model="tipe" class="field-input">
                        @foreach (['pelatihan','mandiri','kkg','webinar','bacaan','lainnya'] as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.input label="Penyelenggara (opsional)" name="penyelenggara" wire:model="penyelenggara" />
                <x-ui.input label="Tautan (opsional)" name="tautan" wire:model="tautan" />
                <x-ui.input label="Tag (pisahkan koma)" name="tagsRaw" wire:model="tagsRaw" hint="mis. aktivasi peserta didik, pertanyaan pemantik, asesmen" />
                <div class="sm:col-span-2"><x-ui.button type="submit">Simpan</x-ui.button></div>
            </form>
        </x-ui.card>
    @endif

    @forelse ($items as $item)
        <x-ui.card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-medium text-ink-900 dark:text-ink-50">{{ $item->judul }}</p>
                        <span class="rounded bg-ink-100 px-1.5 py-0.5 text-xs text-ink-600 dark:bg-ink-800 dark:text-ink-300">{{ ucfirst($item->tipe) }}</span>
                        @if ($item->pemilik_dinas_id === null)
                            <span class="text-xs text-[var(--text-muted)]">Global</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-[var(--text-muted)]">{{ $item->deskripsi }}</p>
                    @if ($item->tags)
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($item->tags as $tag)
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700 dark:bg-brand-900/40 dark:text-brand-200">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($item->tautan)
                        <a href="{{ $item->tautan }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs text-brand-700 hover:underline">Buka tautan ↗</a>
                    @endif
                </div>
                @if ($canManage)
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        <x-ui.status-badge :status="match ($item->status) { 'terbit' => 'done', 'arsip' => 'archived', default => 'draft' }" :label="ucfirst($item->status)" />
                        <div class="flex gap-1">
                            <button wire:click="edit('{{ $item->id }}')" class="text-xs text-brand-700 hover:underline">Sunting</button>
                            @if ($item->status !== 'terbit')
                                <button wire:click="setStatus('{{ $item->id }}','terbit')" class="text-xs text-status-done hover:underline">Terbitkan</button>
                            @else
                                <button wire:click="setStatus('{{ $item->id }}','arsip')" class="text-xs text-[var(--text-muted)] hover:underline">Arsipkan</button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </x-ui.card>
    @empty
        <x-ui.empty-state title="Katalog masih kosong"
            description="{{ $canManage ? 'Tambahkan materi/kegiatan PKB agar dapat direkomendasikan ke guru.' : 'Belum ada materi PKB yang diterbitkan untuk dinas Anda.' }}" />
    @endforelse
</div>
