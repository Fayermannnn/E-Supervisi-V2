<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Verifikasi Email')]
class VerifyEmail extends Component
{
    public function sendVerification(): mixed
    {
        $user = Auth::user();

        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && $user->hasVerifiedEmail()) {
            return $this->redirectRoute('dashboard', navigate: true);
        }

        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        session()->flash('status', 'Tautan verifikasi baru telah dikirim ke email Anda.');

        return null;
    }

    public function logout(): mixed
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirectRoute('login', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.auth.verify-email');
    }
}
