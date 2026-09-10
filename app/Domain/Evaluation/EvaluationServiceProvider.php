<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

use App\Models\EvaluationPanel;
use App\Policies\EvaluationPanelPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EvaluationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(EvaluationPanel::class, EvaluationPanelPolicy::class);
    }
}
