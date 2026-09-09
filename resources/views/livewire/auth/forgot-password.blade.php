<div>
    <p class="mb-5 text-sm text-[var(--text-muted)]">
        Masukkan email akun Anda. Jika terdaftar, kami kirim tautan untuk mengatur ulang kata sandi.
    </p>

    <form wire:submit="sendResetLink" class="space-y-5">
        <x-ui.input
            label="Email"
            name="email"
            type="email"
            autocomplete="username"
            required
            autofocus
            wire:model="email"
        />

        <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
            Kirim tautan
        </x-ui.button>
    </form>

    <p class="mt-5 text-center text-sm text-[var(--text-muted)]">
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700">Kembali ke halaman masuk</a>
    </p>
</div>
