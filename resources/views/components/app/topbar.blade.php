@props(['title' => null])

@php
    $user = auth()->user();
    $initials = \Illuminate\Support\Str::of($user?->name ?? '')->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('');
    $roles = collect($user?->roles() ?? [])->map(fn ($r) => $r->label())->join(', ');
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-[var(--border)] bg-[var(--surface)]/85 px-4 backdrop-blur-md sm:px-6 lg:px-8">
    <button type="button" class="-ml-1 rounded-lg p-1.5 text-ink-500 hover:bg-ink-100 lg:hidden dark:hover:bg-ink-800" @click="$dispatch('toggle-sidebar')" aria-label="Buka menu">
        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
    </button>

    @if ($title)
        <p class="truncate text-sm font-medium text-[var(--text-muted)]">{{ $title }}</p>
    @endif

    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <livewire:notification.bell />

        <div class="hidden h-6 w-px bg-[var(--border)] sm:block"></div>

        <div x-data="{ menu: false }" class="relative">
            <button type="button" @click="menu = !menu" class="flex items-center gap-2.5 rounded-lg py-1 pl-1 pr-2 text-sm hover:bg-ink-100 dark:hover:bg-ink-800">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-100 text-xs font-bold text-brand-800 dark:bg-brand-900 dark:text-brand-100">{{ $initials }}</span>
                <span class="hidden text-left leading-tight sm:block">
                    <span class="block font-medium text-ink-900 dark:text-ink-50">{{ $user?->name }}</span>
                    <span class="block text-[11px] text-[var(--text-muted)]">{{ $roles }}</span>
                </span>
                <svg class="hidden size-4 text-ink-400 sm:block" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div x-show="menu" x-transition.origin.top.right @click.outside="menu = false" x-cloak
                 class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl border border-[var(--border)] bg-[var(--surface)] py-1.5 text-sm shadow-lg">
                <a href="{{ route('profile.edit') }}" wire:navigate class="block px-4 py-2 hover:bg-ink-100 dark:hover:bg-ink-800">Profil saya</a>
                <div class="px-4 py-2">
                    <span class="text-xs font-medium text-[var(--text-muted)]">Tampilan</span>
                    <div class="mt-1.5 grid grid-cols-3 gap-1">
                        @foreach (['light' => 'Terang', 'dark' => 'Gelap', 'system' => 'Sistem'] as $val => $lbl)
                            <button type="button" @click="setTheme('{{ $val }}')"
                                class="rounded-md px-2 py-1 text-xs ring-1 ring-[var(--border)] transition"
                                :class="{ 'bg-brand-700 text-white ring-brand-700': theme === '{{ $val }}' }">{{ $lbl }}</button>
                        @endforeach
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-[var(--border)] pt-1">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-left font-medium text-status-overdue hover:bg-status-overdue/5">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</header>
