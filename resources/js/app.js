import { registerObservationConsole } from './observation-console';

// Livewire 3 sudah memuat Alpine. Daftarkan komponen kustom sebelum start.
document.addEventListener('alpine:init', () => {
    registerObservationConsole(window.Alpine);
});
