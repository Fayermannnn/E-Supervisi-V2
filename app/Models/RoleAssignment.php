<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Audit\Concerns\Auditable;
use App\Support\Enums\Role;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Identity
 *
 * @property string $user_id
 * @property string $role
 * @property string|null $dinas_id
 * @property string|null $assigned_by
 */
class RoleAssignment extends Model
{
    use Auditable, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'role', 'dinas_id', 'assigned_by'];

    public function roleEnum(): Role
    {
        return Role::from($this->role);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Dinas, $this>
     */
    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class);
    }
}
