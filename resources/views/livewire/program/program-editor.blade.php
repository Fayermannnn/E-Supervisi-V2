@php
    $statusTone = match ($program->status) {
        'aktif' => 'progress',
        'selesai' => 'done',
        'dibatalkan' => 'canceled',
        default => 'draft',
    };
@endphp
<div class="space-y-6">
    <x-ui.page-header eyebrow="Program" :title="$program->judul"
        :description="$program->tahun_ajaran.' · '.ucfirst($program->semester)">
        <x-slot:actions>
            <x-ui.status-badge :status="$statusTone" :label="ucfirst($program->status)" />
            <x-ui.button as="a" href="{{ route('programs.index') }}" variant="ghost" wire:navigate>Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('selected') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($program->catatan)
        <x-ui.card><p class="text-sm text-[var(--text-muted)] whitespace-pre-line">{{ $program->catatan }}</p></x-ui.card>
    @endif

    <x-ui.card title="Target guru" subtitle="Pilih guru binaan yang masuk program ini. Guru yang siklusnya sudah dibuat tidak dapat dilepas.">
        @if ($binaan->isEmpty())
            <p class="text-sm text-[var(--text-muted)]">Anda belum memiliki guru binaan aktif.</p>
        @else
            <div class="space-y-2">
                @foreach ($binaan as $guru)
                    @php $isGenerated = $targets->firstWhere('guru_id', $guru->id)?->cycle_id !== null; @endphp
                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-[var(--border)] bg-[var(--surface-muted)]/50 px-3.5 py-2.5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="selected.{{ $guru->id }}" @if($isGenerated && $program->status !== 'draft') disabled @endif
                                   class="rounded border-[var(--border)] text-brand-600 focus:ring-brand-600">
                            <span class="font-medium">{{ $guru->name }}</span>
                        </label>
                        <span class="text-xs text-[var(--text-muted)]">{{ $guru->sekolah?->nama }}</span>
                        <input type="text" wire:model="fokus.{{ $guru->id }}" placeholder="Fokus supervisi (opsional)"
                               class="field-input ml-auto max-w-xs py-1 text-xs">
                        @if ($isGenerated) <x-ui.status-badge status="done" label="Siklus dibuat" /> @endif
                    </div>
                @endforeach
            </div>
            @if ($program->isMutable())
                <div class="mt-4 flex flex-wrap gap-2 border-t border-[var(--border)] pt-4">
                    <x-ui.button wire:click="syncTargets" variant="secondary">Simpan daftar target</x-ui.button>
                    <x-ui.button wire:click="generate" wire:confirm="Buat siklus DRAFT dari target yang belum memiliki siklus?"
                        :disabled="$pendingCount === 0">
                        Generate {{ $pendingCount }} siklus
                    </x-ui.button>
                </div>
            @endif
        @endif
    </x-ui.card>

    <x-ui.card title="Siklus yang dihasilkan">
        @php $generated = $targets->whereNotNull('cycle_id'); @endphp
        @if ($generated->isEmpty())
            <p class="text-sm text-[var(--text-muted)]">Belum ada siklus yang dibuat dari program ini.</p>
        @else
            <ul class="divide-y divide-[var(--border)] text-sm">
                @foreach ($generated as $target)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $target->guru?->name }}</span>
                        <span class="flex items-center gap-3">
                            <x-ui.status-badge :status="$target->cycle?->status->tone() ?? 'draft'" :label="$target->cycle?->status->label() ?? 'Draf'" />
                            <a href="{{ route('cycles.show', $target->cycle_id) }}" wire:navigate class="text-brand-700 hover:underline">Buka</a>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    @if ($program->isMutable())
        <div class="flex gap-2">
            @if ($program->status === 'aktif')
                <x-ui.button wire:click="setStatus('selesai')" variant="ghost">Tandai selesai</x-ui.button>
            @endif
            <x-ui.button wire:click="setStatus('dibatalkan')" wire:confirm="Batalkan program ini?" variant="ghost">Batalkan program</x-ui.button>
        </div>
    @endif
</div>
