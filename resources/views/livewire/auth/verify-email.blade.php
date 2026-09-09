<div class="space-y-5 text-sm text-[var(--text-muted)]">
    <p>
        Terima kasih telah mendaftar. Sebelum mulai, mohon verifikasi email Anda dengan
        mengklik tautan yang kami kirim. Belum menerima email?
    </p>

    <div class="flex items-center gap-3">
        <x-ui.button wire:click="sendVerification">Kirim ulang tautan</x-ui.button>
        <x-ui.button variant="ghost" wire:click="logout">Keluar</x-ui.button>
    </div>
</div>
