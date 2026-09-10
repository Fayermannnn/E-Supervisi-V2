<div class="space-y-6">
    <a href="{{ route('help.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-brand-700 hover:text-brand-800">
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Kembali ke daftar
    </a>

    <x-ui.card>
        <article class="prose prose-sm max-w-none dark:prose-invert prose-headings:font-semibold prose-a:text-brand-600">
            <h1 class="text-xl font-semibold text-ink-900 dark:text-ink-50">{{ $article->title }}</h1>
            {!! $html !!}
        </article>
    </x-ui.card>
</div>
