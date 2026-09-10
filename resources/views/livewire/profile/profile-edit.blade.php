<div class="space-y-6">
    <x-ui.page-header eyebrow="Akun" title="Profil Saya" description="Kelola informasi akun dan preferensi notifikasi." />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Informasi akun">
            <form wire:submit="updateProfile" class="space-y-4">
                <x-ui.input label="Nama lengkap" name="name" wire:model="name" required />
                <x-ui.input label="Email" name="email" type="email" wire:model="email" required
                    hint="Mengubah email akan mereset status verifikasi." />

                <div class="space-y-2 pt-1">
                    <p class="text-sm font-medium text-ink-800 dark:text-ink-100">Notifikasi</p>
                    <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300">
                        <input type="checkbox" wire:model="notifyApp" class="rounded text-brand-600 focus:ring-brand-600"> Dalam aplikasi
                    </label>
                    <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300">
                        <input type="checkbox" wire:model="notifyMail" class="rounded text-brand-600 focus:ring-brand-600"> Email
                    </label>
                </div>

                <x-ui.button type="submit">Simpan</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Ubah kata sandi">
            <form wire:submit="updatePassword" class="space-y-4">
                <x-ui.input label="Kata sandi saat ini" name="current_password" type="password" wire:model="current_password" autocomplete="current-password" />
                <x-ui.input label="Kata sandi baru" name="password" type="password" wire:model="password" autocomplete="new-password" />
                <x-ui.input label="Konfirmasi kata sandi baru" name="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" />
                <x-ui.button type="submit">Perbarui kata sandi</x-ui.button>
            </form>
        </x-ui.card>
    </div>

    <x-ui.card title="Peran & penempatan" subtitle="Dikelola oleh Admin Sistem / Admin Dinas">
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-[var(--text-muted)]">Peran</dt><dd>{{ collect($user->roles())->map(fn ($r) => $r->label())->join(', ') ?: '—' }}</dd></div>
            <div><dt class="text-[var(--text-muted)]">Sekolah</dt><dd>{{ $user->sekolah?->nama ?? '—' }}</dd></div>
            <div><dt class="text-[var(--text-muted)]">NIP</dt><dd>{{ $user->nip ?? '—' }}</dd></div>
            <div><dt class="text-[var(--text-muted)]">Jenis supervisor</dt><dd>{{ $user->supervisor_type?->label() ?? '—' }}</dd></div>
        </dl>
    </x-ui.card>
</div>
