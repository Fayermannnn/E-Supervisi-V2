/**
 * Konsol observasi luring-mampu (ADR-006, docs/offline.md).
 *
 * - State pengisian disimpan ke IndexedDB (bukan hanya draft di memori).
 * - Outbox + retry: perubahan diantre lalu di-flush ke /api/v1/sync/observations.
 * - Idempoten: server memakai observation.id (UUID) + hash payload.
 * - Konflik versi -> ditandai, keputusan penggabungan manual.
 */

const DB_NAME = 'esupervisi-obs';
const DB_VERSION = 1;

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains('state')) db.createObjectStore('state', { keyPath: 'id' });
            if (!db.objectStoreNames.contains('outbox')) db.createObjectStore('outbox', { keyPath: 'key', autoIncrement: true });
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function idbGet(store, key) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(store, 'readonly').objectStore(store).get(key);
        tx.onsuccess = () => resolve(tx.result);
        tx.onerror = () => reject(tx.error);
    });
}

async function idbPut(store, value) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(store, 'readwrite').objectStore(store).put(value);
        tx.onsuccess = () => resolve(tx.result);
        tx.onerror = () => reject(tx.error);
    });
}

async function idbAll(store) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(store, 'readonly').objectStore(store).getAll();
        tx.onsuccess = () => resolve(tx.result || []);
        tx.onerror = () => reject(tx.error);
    });
}

async function idbDelete(store, key) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(store, 'readwrite').objectStore(store).delete(key);
        tx.onsuccess = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/** Buang proxy reaktif Alpine agar aman di-structured-clone ke IndexedDB. */
function plain(value) {
    return JSON.parse(JSON.stringify(value));
}

export function registerObservationConsole(Alpine) {
    Alpine.store('obs', {
        syncState: 'idle',
        pendingCount: 0,
        online: navigator.onLine,
        conflict: null,
    });

    Alpine.data('observationConsole', (config) => ({
        cycleId: config.cycleId,
        observationId: config.observationId,
        instrumentVersionId: config.instrumentVersionId,
        deviceId: config.deviceId,
        editable: config.editable,
        schema: config.schema,
        values: {},
        notes: {},
        catatanSkrip: '',
        version: config.version || 1,
        _timer: null,

        async init() {
            const stored = this.observationId ? await idbGet('state', this.observationId) : null;
            if (stored) {
                this.values = stored.values || {};
                this.notes = stored.notes || {};
                this.catatanSkrip = stored.catatanSkrip || '';
                this.version = stored.version || this.version;
            } else if (config.initial) {
                for (const r of config.initial.responses || []) {
                    this.values[r.item_key] = r.value_numeric ?? r.value_boolean ?? r.value_text ?? r.value_json ?? '';
                    if (r.catatan_item) this.notes[r.item_key] = r.catatan_item;
                }
                this.catatanSkrip = config.initial.catatan_skrip || '';
            }

            this.refreshPending();

            window.addEventListener('online', () => { Alpine.store('obs').online = true; this.flush(); });
            window.addEventListener('offline', () => { Alpine.store('obs').online = false; Alpine.store('obs').syncState = 'offline'; });
            this._interval = setInterval(() => this.flush(), 20000);

            if (this.editable) this.flush();
        },

        get sections() {
            return this.schema?.sections || [];
        },

        async touch(itemKey) {
            if (!this.editable) return;
            Alpine.store('obs').syncState = 'saving-local';
            await this.persistLocal();
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.enqueueAndFlush(), 800);
        },

        async persistLocal() {
            if (!this.observationId) return;
            await idbPut('state', plain({
                id: this.observationId,
                values: this.values,
                notes: this.notes,
                catatanSkrip: this.catatanSkrip,
                version: this.version,
            }));
        },

        async enqueueAndFlush() {
            const responses = [];
            for (const section of this.sections) {
                for (const item of section.items || []) {
                    const v = this.values[item.key];
                    if (v === undefined || v === '') continue;
                    responses.push({
                        item_key: item.key,
                        section_key: section.key,
                        value: v,
                        catatan_item: this.notes[item.key] || null,
                    });
                }
            }

            await idbPut('outbox', {
                payload: plain({
                    id: this.observationId,
                    cycle_id: this.cycleId,
                    base_version: this.version,
                    device_id: this.deviceId,
                    responses,
                    catatan_skrip: this.catatanSkrip,
                    client_updated_at: new Date().toISOString(),
                }),
            });

            await this.refreshPending();
            this.flush();
        },

        async refreshPending() {
            const items = await idbAll('outbox');
            Alpine.store('obs').pendingCount = items.length;
            if (items.length > 0 && Alpine.store('obs').syncState === 'idle') {
                Alpine.store('obs').syncState = 'pending';
            }
        },

        async flush() {
            if (!navigator.onLine) {
                Alpine.store('obs').syncState = 'offline';
                return;
            }
            const items = await idbAll('outbox');
            if (items.length === 0) {
                if (['pending', 'syncing', 'saving-local'].includes(Alpine.store('obs').syncState)) {
                    Alpine.store('obs').syncState = 'synced';
                }
                return;
            }

            Alpine.store('obs').syncState = 'syncing';
            // Kirim hanya payload terbaru per observasi; entri lama jadi usang.
            const latest = items[items.length - 1];

            try {
                const res = await fetch('/api/v1/sync/observations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ observations: [latest.payload] }),
                });

                const body = await res.json();

                if (res.ok) {
                    if ((body.data?.conflicts || []).length > 0) {
                        const c = body.data.conflicts[0];
                        Alpine.store('obs').conflict = c;
                        Alpine.store('obs').syncState = 'conflict';
                        return;
                    }
                    const mine = (body.data?.applied || []).find((a) => a.id === this.observationId);
                    if (mine) {
                        this.version = mine.version;
                        await this.persistLocal();
                    }
                    for (const it of items) await idbDelete('outbox', it.key);
                    await this.refreshPending();
                    Alpine.store('obs').syncState = 'synced';
                    Alpine.store('obs').conflict = null;
                } else {
                    Alpine.store('obs').syncState = 'error';
                }
            } catch (e) {
                Alpine.store('obs').syncState = navigator.onLine ? 'error' : 'offline';
            }
        },

        async resolveConflict(strategy) {
            // strategy: 'server' (buang lokal) | 'local' (paksa timpa dgn versi server sbg base)
            const res = await fetch(`/api/v1/observations/${this.observationId}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const server = (await res.json()).data;

            if (strategy === 'server') {
                this.values = {};
                this.notes = {};
                for (const r of server.responses || []) {
                    this.values[r.item_key] = r.value_numeric ?? r.value_boolean ?? r.value_text ?? r.value_json ?? '';
                    if (r.catatan_item) this.notes[r.item_key] = r.catatan_item;
                }
                this.catatanSkrip = server.catatan_skrip || '';
            }

            this.version = server.version;
            await this.persistLocal();
            const items = await idbAll('outbox');
            for (const it of items) await idbDelete('outbox', it.key);
            Alpine.store('obs').conflict = null;
            Alpine.store('obs').syncState = 'idle';

            if (strategy === 'local') await this.enqueueAndFlush();
        },
    }));
}
