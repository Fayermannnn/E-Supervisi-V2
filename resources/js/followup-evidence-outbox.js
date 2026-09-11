/**
 * Outbox luring khusus bukti RTL (ADR-006, docs/offline.md; checkpoint R-04:
 * "Offline untuk Observasi + bukti RTL"). Pola sama dengan
 * observation-console.js (IndexedDB + retry + idempoten via UUID klien),
 * tapi modul mandiri terpisah — domain berbeda (M5, bukan M2) dan tak perlu
 * berbagi state dengan konsol observasi.
 *
 * - Catatan bukti RTL ditulis ke IndexedDB SEGERA (online maupun luring),
 *   lalu diantre untuk dikirim ke POST /api/v1/sync/follow-up-evidence.
 * - Idempoten: id UUID dibangkitkan klien; server (SubmitFollowUpEvidence)
 *   mengembalikan baris yang sama bila id sudah ada — retry ganda aman.
 * - Setelah sukses, entri dihapus dari outbox + item terkait di-refresh via
 *   $wire.$refresh() agar daftar bukti (dirender server) ikut termutakhirkan.
 */

const DB_NAME = 'esupervisi-followup';
const DB_VERSION = 1;

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains('outbox')) db.createObjectStore('outbox', { keyPath: 'id' });
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function idbPut(value) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readwrite').objectStore('outbox').put(value);
        tx.onsuccess = () => resolve(tx.result);
        tx.onerror = () => reject(tx.error);
    });
}

async function idbAll() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readonly').objectStore('outbox').getAll();
        tx.onsuccess = () => resolve(tx.result || []);
        tx.onerror = () => reject(tx.error);
    });
}

async function idbDelete(id) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readwrite').objectStore('outbox').delete(id);
        tx.onsuccess = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function registerFollowUpEvidenceOutbox(Alpine) {
    Alpine.data('followUpEvidenceOutbox', () => ({
        online: navigator.onLine,
        syncState: 'idle',
        queued: [],

        async init() {
            await this.refreshQueued();

            window.addEventListener('online', () => { this.online = true; this.flush(); });
            window.addEventListener('offline', () => { this.online = false; this.syncState = 'offline'; });
            this._interval = setInterval(() => this.flush(), 20000);

            if (this.online) this.flush();
        },

        async refreshQueued() {
            this.queued = await idbAll();
            if (this.queued.length > 0 && this.syncState === 'idle') {
                this.syncState = 'pending';
            }
        },

        /** Dipanggil dari form per-butir RTL. Menulis ke IndexedDB lalu mencoba kirim. */
        async queueEvidence(followUpItemId, deskripsi) {
            const note = (deskripsi || '').trim();
            if (!note || !followUpItemId) return;

            const id = crypto.randomUUID();
            await idbPut({
                id,
                follow_up_item_id: followUpItemId,
                payload: { id, follow_up_item_id: followUpItemId, tipe: 'catatan', deskripsi: note },
            });

            await this.refreshQueued();
            this.flush();
        },

        queuedFor(followUpItemId) {
            return this.queued.filter((q) => q.follow_up_item_id === followUpItemId);
        },

        async flush() {
            if (!navigator.onLine) {
                this.syncState = 'offline';
                return;
            }

            const items = await idbAll();
            if (items.length === 0) {
                if (['pending', 'syncing'].includes(this.syncState)) this.syncState = 'synced';
                return;
            }

            this.syncState = 'syncing';

            try {
                const res = await fetch('/api/v1/sync/follow-up-evidence', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ evidence: items.map((i) => i.payload) }),
                });

                const body = await res.json();

                if (res.ok) {
                    const appliedIds = new Set((body.data?.applied || []).map((a) => a.id));
                    for (const it of items) {
                        if (appliedIds.has(it.id)) await idbDelete(it.id);
                    }
                    await this.refreshQueued();
                    this.syncState = this.queued.length > 0 ? 'pending' : 'synced';

                    if (appliedIds.size > 0 && this.$wire) this.$wire.$refresh();
                } else {
                    this.syncState = 'error';
                }
            } catch (e) {
                this.syncState = navigator.onLine ? 'error' : 'offline';
            }
        },
    }));
}
