<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev;

use App\Models\BestPractice;
use App\Models\PkbCatalogItem;
use App\Models\PkbRecommendation;
use App\Policies\BestPracticePolicy;
use App\Policies\PkbCatalogItemPolicy;
use App\Policies\PkbRecommendationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProfessionalDevServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(PkbCatalogItem::class, PkbCatalogItemPolicy::class);
        Gate::policy(PkbRecommendation::class, PkbRecommendationPolicy::class);
        Gate::policy(BestPractice::class, BestPracticePolicy::class);
    }
}
