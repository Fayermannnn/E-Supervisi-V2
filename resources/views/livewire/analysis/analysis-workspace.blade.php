<div class="space-y-6">
    @php $isFinal = $result?->isFinal(); @endphp

    <x-ui.page-header eyebrow="Pasca-Observasi" title="Analisis Hasil Observasi" :description="$cycle->judul">
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
                @php
                    $s = $result->score_summary;
                    $band = $s['band'] ?? null;
                    $bandTone = match (true) {
                        $band === null => 'neutral',
                        str_contains(strtolower($band), 'sangat baik'), str_contains(strtolower($band), 'baik') => 'done',
                        str_contains(strtolower($band), 'cukup') => 'progress',
                        default => 'overdue',
                    };
                @endphp
                <div class="grid gap-5 sm:grid-cols-[minmax(0,11rem)_1fr] sm:items-center">
                    <div class="sm:border-r sm:border-[var(--border)] sm:pr-5">
                        <p class="text-4xl font-bold leading-none text-ink-900 dark:text-ink-50">{{ $s['total'] !== null ? number_format($s['total'] * 100, 0).'%' : '—' }}</p>
                        <p class="mt-1 text-xs font-medium text-[var(--text-muted)]">Skor total{{ $band ? ' · '.$band : '' }}</p>
                        @if ($s['total'] !== null)
                            <div class="mt-2.5">
                                <x-ui.meter :value="$s['total']" :tone="$bandTone" :value-label="number_format($s['total'] * 100, 0).'%'" />
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2.5">
                        @forelse ($s['sections'] ?? [] as $key => $sec)
                            <x-ui.meter
                                :label="\Illuminate\Support\Str::headline((string) $key)"
                                :value="$sec['score'] ?? 0"
                                :value-label="$sec['score'] !== null ? number_format($sec['score'] * 100, 0).'%' : '—'" />
                        @empty
                            <p class="text-xs text-[var(--text-muted)]">Tidak ada rincian per seksi.</p>
                        @endforelse
                    </div>
                </div>

                <ul class="mt-5 space-y-2 border-t border-[var(--border)] pt-4 text-sm">
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
                                class="field-input"></textarea>
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
                    <textarea wire:model="ringkasan" rows="8" class="field-input"></textarea>
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
