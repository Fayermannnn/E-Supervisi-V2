<div x-data="{ open: @entangle('open') }" class="relative">
    <button type="button" @click="open = !open" class="relative rounded-full p-2 hover:bg-ink-100 dark:hover:bg-ink-800" aria-label="Notifikasi">
        <svg class="size-5 text-ink-600 dark:text-ink-300" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        @if ($this->unreadCount > 0)
            <span class="absolute right-1 top-1 flex min-w-4 items-center justify-center rounded-full bg-status-overdue px-1 text-[10px] font-semibold text-white">{{ $this->unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-transition @click.outside="open = false" x-cloak
         class="absolute right-0 mt-2 w-80 rounded-lg border border-[var(--border)] bg-[var(--surface)] shadow-lg">
        <div class="flex items-center justify-between border-b border-[var(--border)] px-4 py-2.5">
            <span class="text-sm font-semibold">Notifikasi</span>
            @if ($this->unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs font-medium text-brand-600 hover:text-brand-700">Tandai semua dibaca</button>
            @endif
        </div>

        <div class="max-h-96 divide-y divide-[var(--border)] overflow-y-auto">
            @forelse ($this->recent as $notification)
                <button wire:click="markRead('{{ $notification->id }}')"
                        class="block w-full px-4 py-3 text-left text-sm hover:bg-ink-50 dark:hover:bg-ink-800 {{ $notification->read_at ? 'opacity-60' : '' }}">
                    <p class="font-medium text-ink-800 dark:text-ink-100">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                    <p class="mt-0.5 text-xs text-[var(--text-muted)]">{{ $notification->data['body'] ?? '' }}</p>
                    <p class="mt-1 text-[11px] text-ink-400">{{ $notification->created_at?->diffForHumans() }}</p>
                </button>
            @empty
                <p class="px-4 py-8 text-center text-sm text-[var(--text-muted)]">Belum ada notifikasi.</p>
            @endforelse
        </div>
    </div>
</div>
