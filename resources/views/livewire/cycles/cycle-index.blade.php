<div class="space-y-6">
    <x-ui.page-header title="Siklus Supervisi" description="Kelola siklus supervisi klinis dari perencanaan hingga pelaporan.">
        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button as="a" href="{{ route('cycles.create') }}">Buat siklus</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-wrap gap-2">
        <button wire:click="$set('status', '')" class="rounded-full px-3 py-1 text-xs font-medium {{ $status === '' ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-600 dark:bg-ink-800' }}">Semua</button>
        @foreach ($statuses as $s)
            <button wire:click="$set('status', '{{ $s->value }}')" class="rounded-full px-3 py-1 text-xs font-medium {{ (string) $status === (string) $s->value ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-600 dark:bg-ink-800' }}">
                {{ $s->label() }}
            </button>
        @endforeach
    </div>

    @forelse ($cycles as $cycle)
        <a href="{{ route('cycles.show', $cycle) }}" wire:navigate wire:key="cycle-{{ $cycle->id }}"
           class="block rounded-xl border border-[var(--border)] bg-[var(--surface)] p-5 shadow-sm transition hover:border-brand-300">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-ink-900 dark:text-ink-50">{{ $cycle->judul }}</h2>
                    <p class="mt-0.5 text-sm text-[var(--text-muted)]">
                        Guru: {{ $cycle->guru?->name }} · Supervisor: {{ $cycle->supervisor?->name }} · {{ $cycle->tahun_ajaran }} ({{ ucfirst($cycle->semester) }})
                    </p>
                </div>
                <x-ui.status-badge :status="$cycle->status->tone()" :label="$cycle->status->label()" />
            </div>
            <div class="mt-4">
                <x-ui.cycle-stepper :status="$cycle->status" />
            </div>
        </a>
    @empty
        <x-ui.empty-state title="Belum ada siklus"
            description="{{ $canCreate ? 'Buat siklus baru untuk memulai supervisi guru binaan Anda.' : 'Siklus supervisi Anda akan muncul di sini.' }}" />
    @endforelse

    <div>{{ $cycles->links() }}</div>
</div>
