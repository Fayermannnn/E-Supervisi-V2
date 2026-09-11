<div class="space-y-6" @if ($isGuru) x-data="followUpEvidenceOutbox()" @endif>
    <x-ui.page-header eyebrow="Pasca-Observasi" title="Pelacak Tindak Lanjut (RTL)" :description="$cycle->judul">
        <x-slot:actions>
            @if ($isGuru)
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
                    :class="{
                        'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300': syncState === 'idle',
                        'bg-status-progress/12 text-status-progress': ['pending','syncing'].includes(syncState),
                        'bg-status-done/12 text-status-done': syncState === 'synced',
                        'bg-status-overdue/12 text-status-overdue': ['offline','error'].includes(syncState),
                    }">
                    <span class="size-1.5 rounded-full bg-current" :class="{ 'animate-pulse': ['pending','syncing'].includes(syncState) }"></span>
                    <span x-text="{ idle: 'Siap', offline: 'Luring', pending: 'Menunggu sinkron', syncing: 'Menyinkronkan', synced: 'Tersinkron', error: 'Gagal sinkron' }[syncState] ?? '—'"></span>
                    <span x-show="queued.length > 0" x-text="'(' + queued.length + ')'" class="opacity-70"></span>
                </span>
            @endif
            <x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost">Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($isGuru)
        <p class="text-xs text-[var(--text-muted)]" x-show="!online">
            Anda sedang luring. Catatan bukti tersimpan di perangkat ini dan akan terkirim otomatis begitu koneksi kembali.
        </p>
    @endif

    @error('tujuan') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @foreach ($plans as $plan)
        <x-ui.card :title="$plan->tujuan"
            :subtitle="'Tenggat '.$plan->tenggat->translatedFormat('d M Y')">
            <x-slot:actions>
                <x-ui.status-badge
                    :status="match($plan->status){ 'selesai' => 'done', 'terlambat' => 'overdue', 'dibatalkan' => 'archived', default => 'progress' }"
                    :label="Str::title($plan->status)" />
            </x-slot:actions>

            @php
                $totalItems = $plan->items->count();
                $doneItems = $plan->items->where('status', 'selesai')->count();
            @endphp
            @if ($totalItems > 0)
                <div class="mb-4">
                    <x-ui.meter
                        :value="$doneItems" :max="$totalItems"
                        :tone="$plan->status === 'terlambat' ? 'overdue' : ($doneItems === $totalItems ? 'done' : 'progress')"
                        label="Butir selesai"
                        :value-label="$doneItems.' / '.$totalItems" />
                </div>
            @endif

            <ul class="divide-y divide-[var(--border)]">
                @foreach ($plan->items as $item)
                    <li class="py-3" wire:key="fi-{{ $item->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium {{ $item->status === 'selesai' ? 'text-[var(--text-muted)] line-through' : '' }}">{{ $item->deskripsi }}</p>
                                <p class="text-xs text-[var(--text-muted)]">Indikator: {{ $item->indikator_keberhasilan }}</p>
                            </div>
                            <div class="flex shrink-0 gap-1.5">
                                @if ($item->status !== 'selesai')
                                    <button wire:click="markItem('{{ $item->id }}', 'selesai')" class="rounded px-2 py-1 text-xs font-medium text-status-done ring-1 ring-inset ring-status-done/30">Tandai selesai</button>
                                @else
                                    <button wire:click="markItem('{{ $item->id }}', 'berjalan')" class="rounded px-2 py-1 text-xs text-[var(--text-muted)] ring-1 ring-inset ring-[var(--border)]">Buka lagi</button>
                                @endif
                            </div>
                        </div>

                        @foreach ($item->evidence as $ev)
                            <p class="mt-1 rounded bg-ink-50 px-2 py-1 text-xs dark:bg-ink-800">
                                <span class="font-medium">Bukti ({{ $ev->tipe }}):</span> {{ $ev->deskripsi ?? $ev->url ?? $ev->original_name }}
                            </p>
                        @endforeach

                        @if ($isGuru && $plan->isOpen())
                            {{-- Bukti yang tersimpan lokal tapi belum tersinkron ke server (luring). --}}
                            <template x-for="q in queuedFor('{{ $item->id }}')" :key="q.id">
                                <p class="mt-1 rounded bg-status-progress/10 px-2 py-1 text-xs text-status-progress">
                                    <span class="font-medium">Bukti (menunggu sinkron):</span> <span x-text="q.payload.deskripsi"></span>
                                </p>
                            </template>

                            <div class="mt-2 flex gap-2" x-data="{ note: '' }">
                                <input type="text" x-model="note" @keydown.enter="queueEvidence('{{ $item->id }}', note); note = ''"
                                    data-testid="evidence-input-{{ $item->id }}"
                                    placeholder="Catatan bukti pelaksanaan…" class="field-input flex-1 py-1 text-xs">
                                <button @click="queueEvidence('{{ $item->id }}', note); note = ''"
                                    data-testid="evidence-submit-{{ $item->id }}"
                                    class="rounded bg-brand-600 px-2 py-1 text-xs font-medium text-white">Tambah bukti</button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endforeach

    @if ($isSupervisor && $cycle->status->value >= \App\Support\Enums\CycleStatus::FeedbackGiven->value && $cycle->status->value < \App\Support\Enums\CycleStatus::Reported->value)
        <x-ui.card title="Buat RTL baru">
            <form wire:submit="createPlan" class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Tujuan</label>
                    <textarea wire:model="tujuan" rows="2" class="field-input"></textarea>
                    @error('tujuan') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                </div>
                <x-ui.input label="Tenggat" name="tenggat" type="date" wire:model="tenggat" required />

                <div class="space-y-3">
                    <p class="text-sm font-medium">Butir kegiatan</p>
                    @foreach ($items as $i => $item)
                        <div class="grid gap-2 rounded-md border border-[var(--border)] p-3 sm:grid-cols-2" wire:key="new-{{ $i }}">
                            <input wire:model="items.{{ $i }}.deskripsi" placeholder="Deskripsi kegiatan" class="field-input py-1.5">
                            <input wire:model="items.{{ $i }}.indikator" placeholder="Indikator keberhasilan" class="field-input py-1.5">
                            @if (count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $i }})" class="text-xs text-status-overdue sm:col-span-2 sm:text-left">Hapus butir</button>
                            @endif
                        </div>
                    @endforeach
                    <button type="button" wire:click="addItem" class="text-sm font-medium text-brand-600">+ Tambah butir</button>
                </div>

                <x-ui.button type="submit">Buat RTL</x-ui.button>
            </form>
        </x-ui.card>
    @endif
</div>
