<div>
    <form wire:submit="login" class="space-y-5">
        <x-ui.input
            label="Email"
            name="form.email"
            type="email"
            autocomplete="username"
            required
            autofocus
            wire:model="form.email"
        />

        <x-ui.input
            label="Kata sandi"
            name="form.password"
            type="password"
            autocomplete="current-password"
            required
            wire:model="form.password"
        />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300">
                <input type="checkbox" wire:model="form.remember" class="rounded border-ink-300 text-brand-600 focus:ring-brand-600">
                Ingat saya
            </label>

            <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">
                Lupa kata sandi?
            </a>
        </div>

        <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses…</span>
        </x-ui.button>
    </form>
</div>
