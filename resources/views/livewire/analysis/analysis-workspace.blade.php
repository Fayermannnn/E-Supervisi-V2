<div class="space-y-6">
    @php $isFinal = $result?->isFinal(); @endphp

    <x-ui.page-header title="Analisis Hasil Observasi" :description="$cycle->judul">
        <x-slot:actions>
            <x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost">Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('analysis') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($observation === null)
        <x-ui.empty-state title="Belum ada observasi final" description="Selesaikan observasi sebelum menganalisis." />
    @else
        {{-- SKOR --}}
        <x-ui.card title="Skor & temuan otomatis">
            <x-slot:actions>
                @unless ($isFinal)
                    <x-ui.button wire:click="computeScores" variant="secondary">Hitung ulang skor</x-ui.button>
                @endunless
            </x-slot:actions>

            @if ($result?->score_summary)
                @php $s = $result->score_summary; @endphp
                <div class="flex flex-wrap items-baseline gap-4">
                    <div>
                        <p class="text-3xl font-semibold text-ink-900 dark:text-ink-50">{{ $s['total'] !== null ? number_format($s['total'] * 100, 0).'%' : '—' }}</p>
                        <p class="text-xs text-[var(--text-muted)]">Skor total · {{ $s['band'] ?? '—' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        @foreach ($s['sections'] ?? [] as $key => $sec)
                            <span class="rounded-md bg-ink-100 px-2 py-1 dark:bg-ink-800">{{ $key }}: {{ $sec['score'] !== null ? number_format($sec['score'] * 100, 0).'%' : '—' }}</span>
                        @endforeach
                    </div>
                </div>

                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($findings as $f)
                        <li class="flex gap-2">
                            <span class="mt-0.5 shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium uppercase {{ $f->kategori === 'kekuatan' ? 'bg-status-done/12 text-status-done' : 'bg-status-progress/12 text-status-progress' }}">
                                {{ $f->kategori === 'kekuatan' ? 'Kekuatan' : 'Pengembangan' }}
                            </span>
                            <span>{{ $f->deskripsi }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-[var(--text-muted)]">Klik "Hitung ulang skor" untuk memulai analisis.</p>
            @endif
        </x-ui.card>

        {{-- AI DRAFT --}}
        @if ($result !== null && ! $isFinal)
            <x-ui.card title="Bantuan Asisten AI">
                @if ($generation === null)
                    <p class="text-sm text-[var(--text-muted)]">Minta draf ringkasan; Anda tetap wajib meninjau & menyunting.</p>
                    <x-ui.button wire:click="requestAiDraft" variant="secondary" class="mt-3">Minta draf AI</x-ui.button>
                @elseif ($generation->status === 'pending')
                    <x-ui.alert variant="info">Draf sedang disusun… segarkan halaman sebentar lagi.</x-ui.alert>
                @elseif ($generation->status === 'failed')
                    <x-ui.alert variant="warning">Draf AI gagal. Lanjutkan penulisan manual.</x-ui.alert>
                @else
                    <x-ui.ai-draft-banner :reviewed="$generation->isHumanApproved()" />
                    <pre class="mt-3 whitespace-pre-wrap rounded-md bg-ink-50 p-3 text-sm dark:bg-ink-800">{{ $generation->output }}</pre>

                    @unless ($generation->isHumanApproved() || $generation->review_status === 'rejected')
                        <div class="mt-3 space-y-2">
                            <textarea wire:model="aiEdit" rows="6" placeholder="Sunting di sini bila memilih 'Sunting'…"
                                class="block w-full rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
                            <div class="flex gap-2">
                                <x-ui.button wire:click="reviewAi('accept')" variant="secondary">Terima apa adanya</x-ui.button>
                                <x-ui.button wire:click="reviewAi('edit')" variant="secondary">Pakai suntingan saya</x-ui.button>
                                <x-ui.button wire:click="reviewAi('reject')" variant="ghost">Tolak</x-ui.button>
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-xs text-[var(--text-muted)]">Status tinjauan: {{ $generation->review_status }}</p>
                    @endunless
                @endif
            </x-ui.card>
        @endif

        {{-- RINGKASAN --}}
        <x-ui.card title="Ringkasan analisis final">
            @if ($isFinal)
                <p class="whitespace-pre-line text-sm">{{ $result->ringkasan }}</p>
                <p class="mt-3 text-xs text-[var(--text-muted)]">Dikunci · sumber: {{ $result->sumber }} · {{ $result->finalized_at?->translatedFormat('d M Y H:i') }}</p>
            @else
                <form wire:submit="saveSummary" class="space-y-3">
                    <textarea wire:model="ringkasan" rows="8" class="block w-full rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] focus:ring-2 focus:ring-brand-600"></textarea>
                    @error('ringkasan') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                    <div class="flex gap-2">
                        <x-ui.button type="submit" variant="secondary">Simpan ringkasan</x-ui.button>
                        <x-ui.button type="button" wire:click="finalize" wire:confirm="Kunci analisis? Tidak dapat diubah setelah dikunci.">Kunci analisis final</x-ui.button>
                    </div>
                </form>
            @endif
        </x-ui.card>
    @endif
</div>
