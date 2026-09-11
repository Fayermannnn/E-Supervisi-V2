import { registerObservationConsole } from './observation-console';
import { registerFollowUpEvidenceOutbox } from './followup-evidence-outbox';

// Livewire 3 sudah memuat Alpine. Daftarkan komponen kustom sebelum start.
document.addEventListener('alpine:init', () => {
    registerObservationConsole(window.Alpine);
    registerFollowUpEvidenceOutbox(window.Alpine);
});
