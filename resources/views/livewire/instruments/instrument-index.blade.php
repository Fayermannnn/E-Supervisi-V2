<div class="space-y-6">
    <x-ui.page-header title="Bank Instrumen" description="Repositori Format A–E. Struktur item mengikuti kebijakan daerah/SNP.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button wire:click="create">Tambah instrumen</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.alert variant="warning">
        Instrumen contoh (Format B) <strong>belum tervalidasi</strong> secara psikometrik.
        Validasi Format A–E adalah bagian Artikel 2 riset.
    </x-ui.alert>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($instruments as $instrument)
            <x-ui.card :title="$instrument->nama" :subtitle="'Kode '.$instrument->code.' · '.($instrument->isGlobal() ? 'Global' : 'Dinas')">
                <p class="text-sm text-[var(--text-muted)]">{{ $instrument->deskripsi ?? '—' }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($instrument->versions as $version)
                        <button wire:click="preview('{{ $version->id }}')"
                            class="rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-[var(--border)] {{ $previewId === $version->id ? 'bg-brand-50 text-brand-700' : '' }}">
                            v{{ $version->version }} {{ $version->published_at ? '· terbit' : '· draf' }}
                        </button>
                    @endforeach
                </div>
            </x-ui.card>
        @endforeach
    </div>

    @if ($previewSchema !== null)
        <x-ui.card title="Pratinjau struktur instrumen">
            <div class="space-y-4">
                @foreach ($previewSchema->sections as $section)
                    <div>
                        <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $section->title }}</h3>
                        <ul class="mt-1 divide-y divide-[var(--border)] text-sm">
                            @foreach ($section->items as $item)
                                <li class="flex items-center justify-between py-2">
                                    <span>{{ $item->label }} @if ($item->required) <span class="text-status-overdue">*</span> @endif</span>
                                    <span class="text-xs text-[var(--text-muted)]">{{ $item->type->label() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <x-app.slide-over wire-model="showForm" title="Tambah instrumen">
        <form wire:submit="save" class="space-y-4">
            <x-ui.input label="Kode" name="code" wire:model="code" hint="Huruf/angka, mis. B-KUSTOM" />
            <x-ui.input label="Nama" name="nama" wire:model="nama" />
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Deskripsi</label>
                <textarea wire:model="deskripsi" rows="2" class="block w-full rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Skema (JSON)</label>
                <textarea wire:model="schemaJson" rows="14" class="block w-full rounded-md border-0 px-3 py-2 font-mono text-xs ring-1 ring-inset ring-[var(--border)]"></textarea>
                @error('schemaJson') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>
            <x-ui.button type="submit">Simpan & terbitkan</x-ui.button>
        </form>
    </x-app.slide-over>
</div>
