<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Models\AnnualProgram;
use App\Policies\AnnualProgramPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProgramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(AnnualProgram::class, AnnualProgramPolicy::class);
    }
}
