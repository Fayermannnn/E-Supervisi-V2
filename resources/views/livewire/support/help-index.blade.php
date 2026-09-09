<div class="space-y-6">
    <x-ui.page-header title="Bantuan" description="Panduan penggunaan sistem E-Supervisi." />

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari panduan…"
        class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] focus:ring-2 focus:ring-brand-600 sm:max-w-md">

    @forelse ($grouped as $category => $articles)
        <x-ui.card :title="Str::title($category)">
            <ul class="divide-y divide-[var(--border)]">
                @foreach ($articles as $article)
                    <li>
                        <a href="{{ route('help.show', $article) }}" wire:navigate class="flex items-center justify-between py-3 text-sm hover:text-brand-700">
                            <span>{{ $article->title }}</span>
                            <svg class="size-4 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @empty
        <x-ui.empty-state title="Belum ada artikel bantuan" />
    @endforelse
</div>
