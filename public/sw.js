/**
 * Service Worker E-Supervisi (ADR-006, docs/offline.md).
 *
 * Strategi:
 * - App shell (Vite manifest assets) di-precache saat install.
 * - Navigasi ke /cycles/**\/observe: network-first, fallback ke cache -> /offline.
 * - Aset statis same-origin: stale-while-revalidate.
 * - Permintaan API TIDAK di-cache — sinkronisasi ditangani observation-console.js.
 */

const CACHE = 'esupervisi-shell-v1';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE);
            await cache.addAll([OFFLINE_URL, '/manifest.json', '/icon.svg']);
            try {
                const res = await fetch('/build/manifest.json');
                if (res.ok) {
                    const manifest = await res.json();
                    const assets = Object.values(manifest)
                        .flatMap((e) => [e.file, ...(e.css || [])])
                        .filter(Boolean)
                        .map((f) => '/build/' + f);
                    await cache.addAll(assets);
                }
            } catch (e) {
                /* build manifest belum ada saat dev pertama */
            }
            self.skipWaiting();
        })(),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)));
            await self.clients.claim();
        })(),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/api/')) return;
    if (url.pathname.startsWith('/livewire/')) return;

    // Navigasi konsol observasi: network-first dengan fallback.
    if (request.mode === 'navigate') {
        event.respondWith(
            (async () => {
                try {
                    return await fetch(request);
                } catch (e) {
                    const cache = await caches.open(CACHE);
                    return (await cache.match(request)) || (await cache.match(OFFLINE_URL));
                }
            })(),
        );
        return;
    }

    // Aset statis: stale-while-revalidate.
    if (url.pathname.startsWith('/build/') || url.pathname === '/icon.svg') {
        event.respondWith(
            (async () => {
                const cache = await caches.open(CACHE);
                const cached = await cache.match(request);
                const network = fetch(request).then((res) => {
                    if (res.ok) cache.put(request, res.clone());
                    return res;
                });
                return cached || network;
            })(),
        );
    }
});
