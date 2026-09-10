<?php

declare(strict_types=1);

namespace App\Domain\Accountability;

use App\Models\CalibrationSession;
use App\Models\SupervisorEvaluation;
use App\Policies\CalibrationSessionPolicy;
use App\Policies\SupervisorEvaluationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AccountabilityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SupervisorEvaluation::class, SupervisorEvaluationPolicy::class);
        Gate::policy(CalibrationSession::class, CalibrationSessionPolicy::class);
    }
}
