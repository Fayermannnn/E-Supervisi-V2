<div class="space-y-6">
    <x-ui.page-header eyebrow="Administrasi" title="Audit Log" description="Jejak perubahan data — hanya-baca, tidak dapat diubah atau dihapus." />

    <x-ui.card flush>
        <div class="flex flex-col gap-3 border-b border-[var(--border)] p-4 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari aksi…"
                class="field-input sm:max-w-xs">
            <select wire:model.live="actionFilter" class="field-input">
                <option value="">Semua aksi</option>
                @foreach ($actions as $a) <option value="{{ $a }}">{{ $a }}</option> @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-[var(--text-muted)]">
                    <tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Aktor</th><th class="px-4 py-3">Aksi</th><th class="px-4 py-3">Entitas</th><th class="px-4 py-3">IP</th></tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($logs as $log)
                        <tr wire:key="log-{{ $log->id }}">
                            <td class="px-4 py-3 whitespace-nowrap text-[var(--text-muted)]">{{ $log->created_at?->format('d/m/y H:i:s') }}</td>
                            <td class="px-4 py-3">{{ $log->actor?->name ?? 'Sistem' }} <span class="text-xs text-[var(--text-muted)]">{{ $log->actor_role }}</span></td>
                            <td class="px-4 py-3"><code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs dark:bg-ink-800">{{ $log->action }}</code></td>
                            <td class="px-4 py-3 text-xs text-[var(--text-muted)]">{{ class_basename($log->auditable_type ?? '') }} {{ Str::limit($log->auditable_id ?? '', 8, '') }}</td>
                            <td class="px-4 py-3 text-xs text-[var(--text-muted)]">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-[var(--text-muted)]">Belum ada entri audit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-[var(--border)] p-4">{{ $logs->links() }}</div>
    </x-ui.card>
</div>
