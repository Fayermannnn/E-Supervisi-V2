@props(['title' => null])

@php $user = auth()->user(); @endphp

<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-[var(--border)] bg-[var(--surface)]/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" class="lg:hidden" @click="$dispatch('toggle-sidebar')" aria-label="Buka menu">
        <svg class="size-6 text-ink-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
    </button>

    @if ($title)
        <p class="truncate text-sm font-medium text-ink-500">{{ $title }}</p>
    @endif

    <div class="ml-auto flex items-center gap-3">
        <livewire:notification.bell />

        <div x-data="{ menu: false }" class="relative">
            <button type="button" @click="menu = !menu" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm hover:bg-ink-100 dark:hover:bg-ink-800">
                <span class="flex size-8 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
                    {{ Str::of($user?->name)->explode(' ')->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->implode('') }}
                </span>
                <span class="hidden sm:block">{{ $user?->name }}</span>
            </button>

            <div x-show="menu" x-transition @click.outside="menu = false" x-cloak
                 class="absolute right-0 mt-2 w-52 rounded-lg border border-[var(--border)] bg-[var(--surface)] py-1 text-sm shadow-lg">
                <a href="{{ route('profile.edit') }}" wire:navigate class="block px-4 py-2 hover:bg-ink-100 dark:hover:bg-ink-800">Profil saya</a>
                <div class="px-4 py-2">
                    <span class="text-xs text-[var(--text-muted)]">Tampilan</span>
                    <div class="mt-1 flex gap-1">
                        <button type="button" @click="setTheme('light')" class="rounded px-2 py-1 text-xs ring-1 ring-[var(--border)]" :class="{ 'bg-brand-50 text-brand-700': theme === 'light' }">Terang</button>
                        <button type="button" @click="setTheme('dark')" class="rounded px-2 py-1 text-xs ring-1 ring-[var(--border)]" :class="{ 'bg-brand-50 text-brand-700': theme === 'dark' }">Gelap</button>
                        <button type="button" @click="setTheme('system')" class="rounded px-2 py-1 text-xs ring-1 ring-[var(--border)]" :class="{ 'bg-brand-50 text-brand-700': theme === 'system' }">Sistem</button>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-[var(--border)]">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-left text-status-overdue hover:bg-ink-100 dark:hover:bg-ink-800">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</header>
