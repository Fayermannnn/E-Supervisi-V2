<div class="space-y-6">
    <x-ui.page-header title="PKB &amp; Praktik Baik" :description="'Siklus: '.$cycle->judul">
        <x-slot:actions>
            <x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost" wire:navigate>Kembali ke siklus</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('pkb') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    {{-- REKOMENDASI PKB (M9) --}}
    <x-ui.card title="Rekomendasi PKB" subtitle="Disusun otomatis dari area pengembangan pada analisis final. Bukan keputusan final — guru bebas memilih.">
        <x-slot:actions>
            @if ($isSupervisor)
                <x-ui.button wire:click="generate" variant="secondary">Susun ulang rekomendasi</x-ui.button>
            @endif
        </x-slot:actions>

        @forelse ($recommendations as $rec)
            <div class="border-b border-[var(--border)] py-3 last:border-0">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium">{{ $rec->catalogItem?->judul }}</p>
                        <p class="text-xs text-[var(--text-muted)]">{{ $rec->alasan }}</p>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="rounded bg-ink-100 px-1.5 py-0.5 text-[11px] text-ink-600 dark:bg-ink-800 dark:text-ink-300">
                                {{ $rec->sumber === 'rtl_berulang' ? 'Pola berulang' : 'Dari analisis' }}
                            </span>
                            <x-ui.status-badge :status="match ($rec->status) { 'dipilih' => 'progress', 'selesai' => 'done', 'ditolak' => 'canceled', default => 'draft' }" :label="ucfirst($rec->status)" />
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1 text-xs">
                        @if ($isGuru && $rec->status === 'disarankan')
                            <button wire:click="respond('{{ $rec->id }}','dipilih')" class="text-status-done hover:underline">Pilih</button>
                            <button wire:click="respond('{{ $rec->id }}','ditolak')" class="text-[var(--text-muted)] hover:underline">Lewati</button>
                        @elseif ($rec->status === 'dipilih')
                            <button wire:click="respond('{{ $rec->id }}','selesai')" class="text-status-done hover:underline">Tandai selesai</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-[var(--text-muted)]">
                Belum ada rekomendasi. {{ $isSupervisor ? 'Klik "Susun ulang rekomendasi" setelah analisis final.' : '' }}
            </p>
        @endforelse
    </x-ui.card>

    {{-- PERPUSTAKAAN PRAKTIK BAIK (M10) --}}
    <x-ui.card title="Perpustakaan Praktik Baik">
        @if ($bestPractice)
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <p class="font-medium">{{ $bestPractice->judul }}</p>
                    <x-ui.status-badge :status="match ($bestPractice->status) {
                        'terbit' => 'done', 'menunggu_kurasi' => 'progress', 'menunggu_consent' => 'scheduled',
                        'ditolak' => 'canceled', 'ditarik' => 'archived', default => 'draft' }"
                        :label="str($bestPractice->status)->headline()" />
                </div>
                <p class="text-[var(--text-muted)]">{{ $bestPractice->ringkasan }}</p>

                @if ($isGuru && $bestPractice->status === 'menunggu_consent')
                    <x-ui.alert variant="warning">
                        Supervisor mengusulkan praktik ini untuk dibagikan ke sekolah lain dalam dinas. Publikasi memerlukan persetujuan Anda (UU PDP).
                        <div class="mt-2 flex gap-2">
                            <x-ui.button wire:click="respondConsent(true)">Setujui</x-ui.button>
                            <x-ui.button wire:click="respondConsent(false)" variant="ghost">Tolak</x-ui.button>
                        </div>
                    </x-ui.alert>
                @elseif ($bestPractice->status === 'menunggu_kurasi')
                    <p class="text-xs text-[var(--text-muted)]">Menunggu kurasi Admin Dinas.</p>
                @endif
            </div>
        @elseif ($canNominate)
            @error('bpJudul') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror
            @if ($showNominate)
                <form wire:submit="nominate" class="space-y-3">
                    <x-ui.input label="Judul praktik baik" name="bpJudul" wire:model="bpJudul" />
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium">Ringkasan</label>
                        <textarea wire:model="bpRingkasan" rows="2" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium">Uraian praktik</label>
                        <textarea wire:model="bpPraktik" rows="5" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
                    </div>
                    <x-ui.input label="Tag (pisahkan koma)" name="bpTags" wire:model="bpTags" />
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="bpAnonim" class="rounded border-[var(--border)] text-brand-600 focus:ring-brand-600">
                        Anonimkan nama guru saat diterbitkan
                    </label>
                    <div class="flex gap-2">
                        <x-ui.button type="submit">Kirim nominasi</x-ui.button>
                        <x-ui.button type="button" wire:click="$set('showNominate', false)" variant="ghost">Batal</x-ui.button>
                    </div>
                </form>
            @else
                <div class="flex items-center justify-between gap-4">
                    <p class="text-sm text-[var(--text-muted)]">Siklus ini berskor tinggi. Anda dapat menominasikannya sebagai praktik baik (perlu persetujuan guru).</p>
                    <x-ui.button wire:click="$set('showNominate', true)">Nominasikan</x-ui.button>
                </div>
            @endif
        @else
            <p class="text-sm text-[var(--text-muted)]">Nominasi praktik baik tersedia setelah siklus dilaporkan dan memenuhi ambang skor.</p>
        @endif
    </x-ui.card>
</div>
