@props(['wireModel', 'title' => ''])

<div
    x-data="{ show: @entangle($wireModel) }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
>
    <div class="absolute inset-0 bg-ink-900/40" @click="show = false" x-show="show" x-transition.opacity></div>

    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10">
        <div class="w-screen max-w-md" x-show="show"
             x-transition:enter="transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex h-full flex-col overflow-y-auto border-l border-[var(--border)] bg-[var(--surface)] shadow-xl">
                <div class="flex items-center justify-between border-b border-[var(--border)] px-5 py-4">
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $title }}</h2>
                    <button type="button" @click="show = false" class="text-ink-400 hover:text-ink-600" aria-label="Tutup">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <div class="flex-1 px-5 py-5">{{ $slot }}</div>
            </div>
        </div>
    </div>
</div>
