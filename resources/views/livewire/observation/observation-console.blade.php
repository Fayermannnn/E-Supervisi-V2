<div class="mx-auto max-w-2xl space-y-4"
     x-data="observationConsole({
        cycleId: @js($cycle->id),
        observationId: @js($observationId),
        instrumentVersionId: @js($instrumentVersionId),
        deviceId: @js(md5(request()->userAgent() ?? 'web')),
        editable: @js($editable),
        schema: @js($schema),
        version: @js($observation?->version ?? 1),
        initial: @js($observation ? [
            'catatan_skrip' => $observation->catatan_skrip,
            'responses' => $observation->responses->map(fn ($r) => [
                'item_key' => $r->item_key,
                'value_numeric' => $r->value_numeric,
                'value_boolean' => $r->value_boolean,
                'value_text' => $r->value_text,
                'value_json' => $r->value_json,
                'catatan_item' => $r->catatan_item,
            ]),
        ] : null),
     })">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-ink-900 dark:text-ink-50">Konsol Observasi</h1>
            <p class="text-sm text-[var(--text-muted)]">{{ $cycle->judul }}</p>
        </div>
        <x-ui.sync-indicator />
    </div>

    @unless ($editable)
        <x-ui.alert variant="info">Observasi sudah difinalkan — tampilan hanya-baca.</x-ui.alert>
    @endunless

    {{-- Konflik --}}
    <template x-if="$store.obs.conflict">
        <div class="rounded-md border border-status-overdue/30 bg-status-overdue/10 p-4 text-sm">
            <p class="font-semibold text-status-overdue">Konflik versi</p>
            <p class="mt-1 text-status-overdue/90">
                Server berada di v<span x-text="$store.obs.conflict.server_version"></span>,
                perangkat ini berbasis v<span x-text="$store.obs.conflict.client_base"></span>.
            </p>
            <div class="mt-3 flex gap-2">
                <button @click="resolveConflict('server')" class="rounded-md bg-white px-3 py-1.5 text-xs font-medium ring-1 ring-status-overdue/40">Ambil versi server</button>
                <button @click="resolveConflict('local')" class="rounded-md bg-status-overdue px-3 py-1.5 text-xs font-medium text-white">Timpa dengan isian saya</button>
            </div>
        </div>
    </template>

    <form @submit.prevent class="space-y-5">
        <template x-for="section in sections" :key="section.key">
            <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] shadow-sm">
                <div class="border-b border-[var(--border)] px-5 py-3">
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50" x-text="section.title"></h2>
                </div>
                <div class="divide-y divide-[var(--border)]">
                    <template x-for="item in section.items" :key="item.key">
                        <div class="px-5 py-4">
                            <label class="block text-sm text-ink-800 dark:text-ink-100" x-text="item.label + (item.required ? ' *' : '')"></label>

                            <div class="mt-2">
                                <template x-if="item.type === 'likert' || item.type === 'numeric'">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="n in (item.scale ? (item.scale.max - item.scale.min + 1) : 4)" :key="n">
                                            <button type="button"
                                                @click="values[item.key] = (item.scale ? item.scale.min : 1) + n - 1; touch(item.key)"
                                                :disabled="!editable"
                                                class="flex size-9 items-center justify-center rounded-md text-sm font-medium ring-1 ring-inset ring-[var(--border)] disabled:opacity-50"
                                                :class="values[item.key] === (item.scale ? item.scale.min : 1) + n - 1 ? 'bg-brand-600 text-white ring-brand-600' : 'bg-[var(--surface)]'"
                                                x-text="(item.scale ? item.scale.min : 1) + n - 1"></button>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="item.type === 'boolean'">
                                    <div class="flex gap-2">
                                        <button type="button" @click="values[item.key] = true; touch(item.key)" :disabled="!editable"
                                            class="rounded-md px-3 py-1.5 text-sm ring-1 ring-inset ring-[var(--border)] disabled:opacity-50"
                                            :class="values[item.key] === true ? 'bg-status-done text-white ring-status-done' : ''">Ya</button>
                                        <button type="button" @click="values[item.key] = false; touch(item.key)" :disabled="!editable"
                                            class="rounded-md px-3 py-1.5 text-sm ring-1 ring-inset ring-[var(--border)] disabled:opacity-50"
                                            :class="values[item.key] === false ? 'bg-status-overdue text-white ring-status-overdue' : ''">Tidak</button>
                                    </div>
                                </template>

                                <template x-if="item.type === 'text'">
                                    <textarea x-model="values[item.key]" @input.debounce.800ms="touch(item.key)" :disabled="!editable" rows="2"
                                        class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] disabled:opacity-50"></textarea>
                                </template>
                            </div>

                            <input type="text" x-model="notes[item.key]" @input.debounce.800ms="touch(item.key)" :disabled="!editable"
                                placeholder="Catatan (opsional)"
                                class="mt-2 block w-full rounded-md border-0 bg-transparent px-0 py-1 text-xs text-[var(--text-muted)] ring-0 focus:ring-0" x-show="editable">
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-5 shadow-sm">
            <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Catatan skrip / naratif</label>
            <textarea x-model="catatanSkrip" @input.debounce.1000ms="touch('_skrip')" :disabled="!editable" rows="4"
                class="mt-2 block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] disabled:opacity-50"
                placeholder="Catat peristiwa penting selama observasi…"></textarea>
        </div>
    </form>

    @if ($editable)
        <div class="flex items-center justify-between gap-4 border-t border-[var(--border)] pt-4">
            <p class="text-xs text-[var(--text-muted)]">Isian tersimpan otomatis. Finalisasi butuh koneksi & seluruh item wajib terisi.</p>
            <x-ui.button wire:click="finalize"
                x-bind:disabled="$store.obs.pendingCount > 0 || !$store.obs.online"
                wire:confirm="Finalkan observasi? Setelah final tidak dapat diubah.">
                Finalkan observasi
            </x-ui.button>
        </div>
        @error('finalize') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror
    @endif

    <x-ui.button as="a" href="{{ route('cycles.show', $cycle) }}" variant="ghost">Kembali ke siklus</x-ui.button>
</div>
