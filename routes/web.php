<?php

declare(strict_types=1);

use App\Livewire\Accountability\AccountabilityDashboard;
use App\Livewire\Accountability\CalibrationIndex;
use App\Livewire\Accountability\CalibrationShow;
use App\Livewire\Accountability\SupervisorEvaluationForm;
use App\Livewire\Admin\Assignments\AssignmentIndex;
use App\Livewire\Admin\Audit\AuditLogIndex;
use App\Livewire\Admin\Organizations\OrganizationIndex;
use App\Livewire\Admin\Policies\PolicyIndex;
use App\Livewire\Admin\Users\UserIndex;
use App\Livewire\Analysis\AnalysisWorkspace;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Cycles\CycleCreate;
use App\Livewire\Cycles\CycleIndex;
use App\Livewire\Cycles\CycleShow;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Evaluation\ExpertReviewForm;
use App\Livewire\Evaluation\PanelIndex;
use App\Livewire\Evaluation\PanelShow;
use App\Livewire\Feedback\FeedbackRoom;
use App\Livewire\FollowUp\FollowUpTracker;
use App\Livewire\Instruments\InstrumentIndex;
use App\Livewire\Observation\ObservationConsole;
use App\Livewire\Planning\PlanningEditor;
use App\Livewire\ProfessionalDev\BestPracticeLibrary;
use App\Livewire\ProfessionalDev\CyclePkb;
use App\Livewire\ProfessionalDev\PkbCatalogIndex;
use App\Livewire\Profile\ProfileEdit;
use App\Livewire\Program\ProgramEditor;
use App\Livewire\Program\ProgramIndex;
use App\Livewire\Reporting\AggregateDashboard;
use App\Livewire\Reporting\CycleReport;
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
    Route::get('/cycles/{cycle}/analysis', AnalysisWorkspace::class)->name('cycles.analysis');
    Route::get('/cycles/{cycle}/feedback', FeedbackRoom::class)->name('cycles.feedback');
    Route::get('/cycles/{cycle}/follow-up', FollowUpTracker::class)->name('cycles.follow-up');
    Route::get('/cycles/{cycle}/report', CycleReport::class)->name('cycles.report');

    // Fase 4 — pengembangan profesional & akuntabilitas
    Route::get('/cycles/{cycle}/pkb', CyclePkb::class)->name('cycles.pkb');
    Route::get('/cycles/{cycle}/evaluate', SupervisorEvaluationForm::class)->name('cycles.evaluate');

    Route::get('/programs', ProgramIndex::class)->name('programs.index');
    Route::get('/programs/{program}', ProgramEditor::class)->name('programs.show');

    Route::get('/pkb/catalog', PkbCatalogIndex::class)->name('pkb.catalog');
    Route::get('/best-practices', BestPracticeLibrary::class)->name('best-practices.index');

    Route::get('/accountability', AccountabilityDashboard::class)->name('accountability.index');
    Route::get('/calibration', CalibrationIndex::class)->name('calibration.index');
    Route::get('/calibration/{session}', CalibrationShow::class)->name('calibration.show');

    // Fase 5 — evaluasi ahli (DSR Artikel 3)
    Route::get('/evaluation', PanelIndex::class)->name('evaluation.index');
    Route::get('/evaluation/{panel}', PanelShow::class)->name('evaluation.show');
    Route::get('/evaluation/{panel}/review', ExpertReviewForm::class)->name('evaluation.review');

    // Bank instrumen (M8)
    Route::get('/instruments', InstrumentIndex::class)->name('instruments.index');

    // Pelaporan agregat (M6)
    Route::get('/reports', AggregateDashboard::class)->name('reports.aggregate');

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
