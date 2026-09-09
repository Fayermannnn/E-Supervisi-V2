<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Masuk')]
class Login extends Component
{
    public LoginForm $form;

    public function login(): mixed
    {
        $this->form->authenticate();

        $user = Auth::user();

        if ($user instanceof User && ! $user->isActive()) {
            Auth::logout();

            $this->addError('form.email', 'Akun Anda dinonaktifkan. Hubungi Admin Sistem.');

            return null;
        }

        Session::regenerate();

        return $this->redirectIntended(route('dashboard'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.auth.login');
    }
}
