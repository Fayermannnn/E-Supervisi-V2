<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Models\AuditLog;
use App\Models\Dinas;
use App\Models\HelpArticle;
use App\Models\PolicySetting;
use App\Models\Sekolah;
use App\Models\SupervisorAssignment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\DinasPolicy;
use App\Policies\HelpArticlePolicy;
use App\Policies\PolicySettingPolicy;
use App\Policies\SekolahPolicy;
use App\Policies\SupervisorAssignmentPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\UserPolicy;
use App\Support\Enums\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        User::class => UserPolicy::class,
        Dinas::class => DinasPolicy::class,
        Sekolah::class => SekolahPolicy::class,
        SupervisorAssignment::class => SupervisorAssignmentPolicy::class,
        PolicySetting::class => PolicySettingPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        SupportTicket::class => SupportTicketPolicy::class,
        HelpArticle::class => HelpArticlePolicy::class,
    ];

    public function boot(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Coarse role gate per permission (docs/rbac.md). Per-record scoping
        // stays in the policies above.
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, static function (User $user) use ($permission): bool {
                return $user->isActive() && $user->hasPermission($permission);
            });
        }
    }
}
