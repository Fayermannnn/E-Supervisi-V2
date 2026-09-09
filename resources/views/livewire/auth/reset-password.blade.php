<div>
    <form wire:submit="resetPassword" class="space-y-5">
        <x-ui.input label="Email" name="email" type="email" autocomplete="username" required wire:model="email" />
        <x-ui.input label="Kata sandi baru" name="password" type="password" autocomplete="new-password" required autofocus wire:model="password" />
        <x-ui.input label="Konfirmasi kata sandi" name="password_confirmation" type="password" autocomplete="new-password" required wire:model="password_confirmation" />

        <x-ui.button type="submit" class="w-full">Atur ulang kata sandi</x-ui.button>
    </form>
</div>
