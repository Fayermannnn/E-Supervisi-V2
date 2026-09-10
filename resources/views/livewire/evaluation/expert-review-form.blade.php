<div class="mx-auto max-w-3xl space-y-6">
    <x-ui.page-header :title="'Penilaian Ahli — '.$panel->judul"
        :description="'Artefak: '.$panel->artefak_versi.'. Nilai setiap aspek dari sisi relevansi (CVR) dan kualitas (Aiken 1–5), lalu isi kuesioner usability.'" />

    @if ($panel->deskripsi)
        <x-ui.alert variant="info">{{ $panel->deskripsi }}</x-ui.alert>
    @endif

    @error('form') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    <form wire:submit="submit" class="space-y-6">
        <x-ui.card title="Aspek artefak">
            <div class="space-y-5">
                @foreach ($aspects as $key => $label)
                    <div class="border-b border-[var(--border)] pb-4 last:border-0 last:pb-0">
                        <p class="text-sm font-medium">{{ $label }}</p>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            <div>
                                <span class="text-xs text-[var(--text-muted)]">Relevansi</span>
                                <div class="mt-1 flex gap-2">
                                    @foreach ($relevansiOptions as $opt)
                                        <label class="flex-1 cursor-pointer rounded-md border px-2 py-1.5 text-center text-xs
                                            {{ ($relevansi[$key] ?? null) === $opt ? 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-900/40' : 'border-[var(--border)]' }}">
                                            <input type="radio" wire:model="relevansi.{{ $key }}" value="{{ $opt }}" class="sr-only">
                                            {{ str($opt)->headline() }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <span class="text-xs text-[var(--text-muted)]">Kualitas (1–5)</span>
                                <div class="mt-1 flex gap-1.5">
                                    @foreach (range(1, 5) as $n)
                                        <label class="flex-1 cursor-pointer rounded-md border py-1.5 text-center text-xs
                                            {{ (int) ($kualitas[$key] ?? 0) === $n ? 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-900/40' : 'border-[var(--border)]' }}">
                                            <input type="radio" wire:model="kualitas.{{ $key }}" value="{{ $n }}" class="sr-only">
                                            {{ $n }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Kuesioner usability (SUS)" subtitle="1 = sangat tidak setuju · 5 = sangat setuju">
            <div class="space-y-3">
                @foreach ($susItems as $key => $item)
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="text-sm">{{ $loop->iteration }}. {{ $item['teks'] }}</span>
                        <div class="flex gap-1.5">
                            @foreach (range(1, 5) as $n)
                                <label class="cursor-pointer rounded-md border px-2.5 py-1 text-xs
                                    {{ (int) ($sus[$key] ?? 0) === $n ? 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-900/40' : 'border-[var(--border)]' }}">
                                    <input type="radio" wire:model="sus.{{ $key }}" value="{{ $n }}" class="sr-only">
                                    {{ $n }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <div class="space-y-1.5">
            <label class="block text-sm font-medium">Catatan / rekomendasi perbaikan <span class="text-[var(--text-muted)]">(opsional)</span></label>
            <textarea wire:model="catatan" rows="4" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
        </div>

        <div class="flex items-center gap-3">
            <x-ui.button type="submit">{{ $submitted ? 'Perbarui penilaian' : 'Kirim penilaian' }}</x-ui.button>
            @if ($submitted)
                <span class="text-xs text-status-done">✓ Terkirim — dapat disunting selama panel masih berjalan.</span>
            @endif
        </div>
    </form>
</div>
