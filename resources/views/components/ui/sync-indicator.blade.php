{{-- Dikendalikan Alpine: state di $store.obs (observation-console.js) --}}
<div x-data class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
     :class="{
        'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300': $store.obs?.syncState === 'idle',
        'bg-status-progress/12 text-status-progress': ['saving-local','pending','syncing'].includes($store.obs?.syncState),
        'bg-status-done/12 text-status-done': $store.obs?.syncState === 'synced',
        'bg-status-overdue/12 text-status-overdue': ['offline','conflict','error'].includes($store.obs?.syncState),
     }">
    <span class="size-1.5 rounded-full bg-current"
          :class="{ 'animate-pulse': ['saving-local','pending','syncing'].includes($store.obs?.syncState) }"></span>
    <span x-text="{
        idle: 'Siap',
        offline: 'Luring',
        'saving-local': 'Menyimpan lokal',
        pending: 'Menunggu sinkron',
        syncing: 'Menyinkronkan',
        synced: 'Tersinkron',
        conflict: 'Konflik — perlu tinjauan',
        error: 'Gagal sinkron',
    }[$store.obs?.syncState] ?? '—'"></span>
    <span x-show="$store.obs?.pendingCount > 0" x-text="'(' + $store.obs.pendingCount + ')'" class="opacity-70"></span>
</div>
