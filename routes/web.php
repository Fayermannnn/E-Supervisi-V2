<?php

declare(strict_types=1);

use App\Livewire\Admin\Assignments\AssignmentIndex;
use App\Livewire\Admin\Audit\AuditLogIndex;
use App\Livewire\Admin\Organizations\OrganizationIndex;
use App\Livewire\Admin\Policies\PolicyIndex;
use App\Livewire\Admin\Users\UserIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Cycles\CycleCreate;
use App\Livewire\Cycles\CycleIndex;
use App\Livewire\Cycles\CycleShow;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Instruments\InstrumentIndex;
use App\Livewire\Observation\ObservationConsole;
use App\Livewire\Planning\PlanningEditor;
use App\Livewire\Profile\ProfileEdit;
use App\Livewire\Support\HelpIndex;
use App\Livewire\Support\HelpShow;
use App\Livewire\Support\SupportTickets;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): RedirectResponse => redirect()->route(Auth::check() ? 'dashboard' : 'login'));

Route::view('/offline', 'offline')->name('offline');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', function (Request $request): RedirectResponse {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    // Email verification
    Route::get('/verify-email', VerifyEmail::class)->name('verification.notice');

    Route::get('/verify-email/{id}/{hash}', function (EmailVerificationRequest $request): RedirectResponse {
        $request->fulfill();

        return redirect()->route('dashboard')->with('status', 'Email berhasil diverifikasi.');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request): RedirectResponse {
        $request->user()?->sendEmailVerificationNotification();

        return back()->with('status', 'Tautan verifikasi baru telah dikirim.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/profile', ProfileEdit::class)->name('profile.edit');

    // Siklus supervisi (Fase 2 — M1, M2)
    Route::get('/cycles', CycleIndex::class)->name('cycles.index');
    Route::get('/cycles/create', CycleCreate::class)->name('cycles.create');
    Route::get('/cycles/{cycle}', CycleShow::class)->name('cycles.show');
    Route::get('/cycles/{cycle}/planning', PlanningEditor::class)->name('cycles.planning');
    Route::get('/cycles/{cycle}/observe', ObservationConsole::class)->name('cycles.observe');

    // Bank instrumen (M8)
    Route::get('/instruments', InstrumentIndex::class)->name('instruments.index');

    Route::get('/help', HelpIndex::class)->name('help.index');
    Route::get('/help/{article:slug}', HelpShow::class)->name('help.show');
    Route::get('/support/tickets', SupportTickets::class)->name('support.tickets');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', UserIndex::class)->name('users.index');
        Route::get('/organizations', OrganizationIndex::class)->name('organizations.index');
        Route::get('/assignments', AssignmentIndex::class)->name('assignments.index');
        Route::get('/policies', PolicyIndex::class)->name('policies.index');
        Route::get('/audit-logs', AuditLogIndex::class)->name('audit.index');
    });
});
