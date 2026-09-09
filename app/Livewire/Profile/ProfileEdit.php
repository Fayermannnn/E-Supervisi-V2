<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Profil Saya')]
class ProfileEdit extends Component
{
    public string $name = '';

    public string $email = '';

    public bool $notifyApp = true;

    public bool $notifyMail = true;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = $this->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $prefs = $user->notification_preferences ?? [];
        $this->notifyApp = (bool) ($prefs['app'] ?? true);
        $this->notifyMail = (bool) ($prefs['mail'] ?? true);
    }

    public function updateProfile(): void
    {
        $user = $this->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->notification_preferences = ['app' => $this->notifyApp, 'mail' => $this->notifyMail];
        $user->save();

        $this->dispatch('notify', message: 'Profil diperbarui.');
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $this->user()->update(['password' => Hash::make($validated['password'])]);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('notify', message: 'Kata sandi diperbarui.');
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }

    public function render(): mixed
    {
        return view('livewire.profile.profile-edit', ['user' => $this->user()]);
    }
}
