<?php

declare(strict_types=1);

namespace App\Domain\Analysis;

use App\Models\AnalysisResult;
use App\Policies\AnalysisResultPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AnalysisServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(AnalysisResult::class, AnalysisResultPolicy::class);
    }
}
