<div class="mx-auto max-w-2xl space-y-6">
    @php $confirmed = $session?->isConfirmed(); @endphp

    <x-ui.page-header eyebrow="Pasca-Observasi" title="Ruang Umpan Balik" :description="$cycle->judul">
        <x-slot:actions><x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost">Kembali</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    @error('konten') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    <x-ui.card flush>
        <div class="max-h-[28rem] space-y-4 overflow-y-auto p-5">
            @forelse ($messages as $m)
                <div class="flex flex-col {{ $m->peran === 'supervisor' ? 'items-start' : 'items-end' }}">
                    <div class="max-w-[85%] rounded-lg px-3.5 py-2 text-sm {{ $m->peran === 'supervisor' ? 'bg-ink-100 dark:bg-ink-800' : 'bg-brand-50 text-brand-900 dark:bg-brand-900/40 dark:text-brand-50' }}">
                        <p class="mb-0.5 text-[10px] font-medium uppercase tracking-wide opacity-60">
                            {{ $m->pengirim?->name }} · {{ Str::of($m->tipe)->replace('_', ' ')->title() }}
                            @if ($m->ai_generation_id) · via AI (ditinjau) @endif
                        </p>
                        <p class="whitespace-pre-line">{{ $m->konten }}</p>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-[var(--text-muted)]">Belum ada percakapan.</p>
            @endforelse
        </div>

        @unless ($confirmed)
            <div class="border-t border-[var(--border)] p-4">
                <form wire:submit="send" class="space-y-2">
                    <div class="flex gap-2">
                        <select wire:model="tipe" class="field-input py-1.5 text-xs">
                            <option value="observasi">Observasi</option>
                            <option value="pertanyaan_reflektif">Pertanyaan reflektif</option>
                            <option value="tanggapan">Tanggapan</option>
                            <option value="kesepakatan">Kesepakatan</option>
                        </select>
                        @if ($isSupervisor)
                            <x-ui.button type="button" wire:click="requestAiSuggestion" variant="ghost" class="text-xs">Minta saran AI</x-ui.button>
                        @endif
                    </div>
                    <textarea wire:model="konten" rows="3" class="field-input"></textarea>
                    <x-ui.button type="submit">Kirim</x-ui.button>
                </form>
            </div>
        @endunless
    </x-ui.card>

    {{-- AI draft menunggu tinjauan supervisor --}}
    @foreach ($pendingAi as $gen)
        <x-ui.card title="Saran AI menunggu tinjauan">
            <x-ui.ai-draft-banner />
            <pre class="mt-2 whitespace-pre-wrap rounded-md bg-ink-50 p-3 text-sm dark:bg-ink-800">{{ $gen->output }}</pre>
            @if ($isSupervisor && $gen->status === 'draft')
                <textarea wire:model="aiEdit" rows="4" placeholder="Sunting bila perlu…" class="field-input mt-2"></textarea>
                <div class="mt-2 flex gap-2">
                    <x-ui.button wire:click="reviewAi('{{ $gen->id }}', 'accept')" variant="secondary">Kirim apa adanya</x-ui.button>
                    <x-ui.button wire:click="reviewAi('{{ $gen->id }}', 'edit')" variant="secondary">Kirim suntingan</x-ui.button>
                    <x-ui.button wire:click="reviewAi('{{ $gen->id }}', 'reject')" variant="ghost">Tolak</x-ui.button>
                </div>
            @endif
        </x-ui.card>
    @endforeach

    {{-- Konfirmasi guru --}}
    @if ($session !== null && ! $confirmed && $isGuru && $messages->isNotEmpty())
        <x-ui.card>
            <p class="text-sm text-[var(--text-muted)]">Setelah membaca umpan balik, konfirmasi penerimaan agar siklus lanjut ke tindak lanjut.</p>
            <x-ui.button wire:click="acknowledge" class="mt-3">Saya menerima umpan balik ini</x-ui.button>
        </x-ui.card>
    @elseif ($confirmed)
        <x-ui.alert variant="success">Umpan balik telah dikonfirmasi guru pada {{ $session->dikonfirmasi_guru_at?->translatedFormat('d M Y H:i') }}.</x-ui.alert>
    @endif
</div>
