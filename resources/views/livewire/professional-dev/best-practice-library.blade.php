<div class="space-y-6">
    <x-ui.page-header title="Perpustakaan Praktik Baik"
        description="Kurasi praktik pembelajaran baik antar-guru dari siklus berskor tinggi sebagai referensi tindak lanjut." />

    @error('curate') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($canCurate && $queue->isNotEmpty())
        <x-ui.card title="Antrean kurasi" subtitle="Disetujui guru, menunggu keputusan Anda.">
            @foreach ($queue as $bp)
                <div class="border-b border-[var(--border)] py-3 last:border-0">
                    <p class="text-sm font-medium">{{ $bp->judul }}</p>
                    <p class="text-xs text-[var(--text-muted)]">{{ $bp->guru?->name }} · skor {{ $bp->skor_band ?? '—' }}</p>
                    <p class="mt-1 text-sm">{{ $bp->ringkasan }}</p>
                    <details class="mt-1 text-sm"><summary class="cursor-pointer text-brand-600">Uraian lengkap</summary>
                        <p class="mt-1 whitespace-pre-line text-[var(--text-muted)]">{{ $bp->praktik }}</p>
                    </details>
                    <input type="text" wire:model="catatan.{{ $bp->id }}" placeholder="Catatan kurasi (opsional)"
                           class="mt-2 block w-full rounded-md border-0 bg-[var(--surface)] px-2.5 py-1 text-xs ring-1 ring-inset ring-[var(--border)]">
                    <div class="mt-2 flex gap-2">
                        <x-ui.button wire:click="curate('{{ $bp->id }}', true)">Terbitkan</x-ui.button>
                        <x-ui.button wire:click="curate('{{ $bp->id }}', false)" variant="ghost">Tolak</x-ui.button>
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    @forelse ($published as $bp)
        <x-ui.card :title="$bp->judul" :subtitle="$bp->displayGuruName().' · '.$bp->terbit_at?->translatedFormat('d M Y')">
            <p class="text-sm">{{ $bp->ringkasan }}</p>
            <p class="mt-2 whitespace-pre-line text-sm text-[var(--text-muted)]">{{ $bp->praktik }}</p>
            @if ($bp->tags)
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach ($bp->tags as $tag)
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700 dark:bg-brand-900/40 dark:text-brand-200">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            @if ($canCurate)
                <button wire:click="withdraw('{{ $bp->id }}')" wire:confirm="Tarik entri ini dari peredaran?"
                        class="mt-3 text-xs text-status-overdue hover:underline">Tarik entri</button>
            @endif
        </x-ui.card>
    @empty
        <x-ui.empty-state title="Belum ada praktik baik terbit"
            description="Praktik baik muncul di sini setelah dinominasikan supervisor, disetujui guru, dan dikurasi Admin Dinas." />
    @endforelse
</div>
