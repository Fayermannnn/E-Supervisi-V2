<?php

declare(strict_types=1);

namespace App\Domain\Feedback;

use App\Models\FeedbackSession;
use App\Policies\FeedbackSessionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FeedbackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(FeedbackSession::class, FeedbackSessionPolicy::class);
    }
}
