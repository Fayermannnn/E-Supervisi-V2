<div class="space-y-6">
    <x-ui.page-header title="Pelacak Tindak Lanjut (RTL)" :description="$cycle->judul">
        <x-slot:actions><x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost">Kembali</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    @error('tujuan') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @foreach ($plans as $plan)
        <x-ui.card :title="$plan->tujuan"
            :subtitle="'Tenggat '.$plan->tenggat->translatedFormat('d M Y')">
            <x-slot:actions>
                <x-ui.status-badge
                    :status="match($plan->status){ 'selesai' => 'done', 'terlambat' => 'overdue', 'dibatalkan' => 'archived', default => 'progress' }"
                    :label="Str::title($plan->status)" />
            </x-slot:actions>

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
                            <div class="mt-2 flex gap-2">
                                <input type="text" wire:model="evidenceNote.{{ $item->id }}" placeholder="Catatan bukti pelaksanaan…"
                                    class="flex-1 rounded-md border-0 px-2 py-1 text-xs ring-1 ring-inset ring-[var(--border)]">
                                <button wire:click="addEvidence('{{ $item->id }}')" class="rounded bg-brand-600 px-2 py-1 text-xs font-medium text-white">Tambah bukti</button>
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
                    <textarea wire:model="tujuan" rows="2" class="block w-full rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
                    @error('tujuan') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                </div>
                <x-ui.input label="Tenggat" name="tenggat" type="date" wire:model="tenggat" required />

                <div class="space-y-3">
                    <p class="text-sm font-medium">Butir kegiatan</p>
                    @foreach ($items as $i => $item)
                        <div class="grid gap-2 rounded-md border border-[var(--border)] p-3 sm:grid-cols-2" wire:key="new-{{ $i }}">
                            <input wire:model="items.{{ $i }}.deskripsi" placeholder="Deskripsi kegiatan" class="rounded-md border-0 px-2 py-1.5 text-sm ring-1 ring-inset ring-[var(--border)]">
                            <input wire:model="items.{{ $i }}.indikator" placeholder="Indikator keberhasilan" class="rounded-md border-0 px-2 py-1.5 text-sm ring-1 ring-inset ring-[var(--border)]">
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
