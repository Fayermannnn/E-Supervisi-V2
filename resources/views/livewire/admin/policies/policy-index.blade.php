<div class="space-y-6">
    <x-ui.page-header eyebrow="Administrasi" title="Konfigurasi Kebijakan" description="Nilai default berlaku global; setiap dinas dapat menimpanya." />

    @if ($dinasOptions->isNotEmpty())
        <x-ui.card>
            <label class="block text-sm font-medium">Lingkup</label>
            <select wire:model.live="scopeDinasId" class="field-input mt-1.5 sm:max-w-sm">
                <option value="">Default global</option>
                @foreach ($dinasOptions as $d) <option value="{{ $d->id }}">{{ $d->nama }}</option> @endforeach
            </select>
        </x-ui.card>
    @endif

    <form wire:submit="save">
        <x-ui.card title="Kebijakan" subtitle="Perubahan tercatat di audit log">
            <div class="divide-y divide-[var(--border)]">
                @foreach ($definitions as $key => $def)
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="max-w-md">
                            <p class="text-sm font-medium text-ink-900 dark:text-ink-50">{{ $def['description'] }}</p>
                            <p class="text-xs text-[var(--text-muted)]">{{ $key }}</p>
                        </div>
                        <div class="shrink-0">
                            @if ($def['type'] === 'boolean')
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="values.{{ $key }}" class="rounded text-brand-600 focus:ring-brand-600"> Aktif
                                </label>
                            @elseif ($def['type'] === 'integer')
                                <input type="number" wire:model="values.{{ $key }}" class="field-input w-28 py-1.5">
                            @else
                                <input type="text" wire:model="values.{{ $key }}" class="field-input w-40 py-1.5" placeholder="mis. 3,1">
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4"><x-ui.button type="submit">Simpan kebijakan</x-ui.button></div>
        </x-ui.card>
    </form>
</div>
