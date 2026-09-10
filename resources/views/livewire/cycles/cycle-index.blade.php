<div class="space-y-6">
    <x-ui.page-header eyebrow="Siklus" title="Siklus Supervisi"
        description="Kelola siklus supervisi klinis dari perencanaan hingga pelaporan berbasis data.">
        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button as="a" href="{{ route('cycles.create') }}">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                    Buat siklus
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="-mx-1 flex gap-1 overflow-x-auto pb-1">
        <button wire:click="$set('status', '')"
            @class([
                'shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition',
                'bg-brand-700 text-white' => $status === '',
                'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800' => $status !== '',
            ])>Semua</button>
        @foreach ($statuses as $s)
            <button wire:click="$set('status', '{{ $s->value }}')"
                @class([
                    'shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition',
                    'bg-brand-700 text-white' => (string) $status === (string) $s->value,
                    'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800' => (string) $status !== (string) $s->value,
                ])>{{ $s->label() }}</button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($cycles as $cycle)
            <a href="{{ route('cycles.show', $cycle) }}" wire:navigate wire:key="cycle-{{ $cycle->id }}"
               class="group block rounded-xl border border-[var(--border)] bg-[var(--surface)] p-5 shadow-xs transition hover:border-brand-300 hover:shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-ink-900 group-hover:text-brand-800 dark:text-ink-50 dark:group-hover:text-brand-200">{{ $cycle->judul }}</h2>
                        <p class="mt-0.5 text-sm text-[var(--text-muted)]">
                            {{ $cycle->guru?->name }} · dibimbing {{ $cycle->supervisor?->name }} · {{ $cycle->tahun_ajaran }} ({{ ucfirst($cycle->semester) }})
                        </p>
                    </div>
                    <x-ui.status-badge :status="$cycle->status->tone()" :label="$cycle->status->label()" />
                </div>
                <div class="mt-5 border-t border-[var(--border)] pt-4">
                    <x-ui.cycle-stepper :status="$cycle->status" />
                </div>
            </a>
        @empty
            <x-ui.empty-state icon="clipboard" title="Belum ada siklus"
                description="{{ $canCreate ? 'Buat siklus baru untuk memulai supervisi guru binaan Anda.' : 'Siklus supervisi Anda akan muncul di sini.' }}">
                @if ($canCreate)
                    <x-slot:action>
                        <x-ui.button as="a" href="{{ route('cycles.create') }}">Buat siklus pertama</x-ui.button>
                    </x-slot:action>
                @endif
            </x-ui.empty-state>
        @endforelse
    </div>

    @if ($cycles->hasPages())
        <div>{{ $cycles->links() }}</div>
    @endif
</div>
