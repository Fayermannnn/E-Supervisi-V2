<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Lupa Kata Sandi')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public function sendResetLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        // Selalu tampilkan pesan generik agar tidak membocorkan keberadaan akun.
        session()->flash('status', __(
            $status === Password::RESET_LINK_SENT
                ? $status
                : 'passwords.sent'
        ));
    }

    public function render(): mixed
    {
        return view('livewire.auth.forgot-password');
    }
}
