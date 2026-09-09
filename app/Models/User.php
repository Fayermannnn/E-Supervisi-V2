<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasRoles;
use App\Support\Enums\SupervisorType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @domain Identity
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $nip
 * @property string|null $jabatan
 * @property SupervisorType|null $supervisor_type
 * @property string|null $sekolah_id
 * @property bool $is_active
 * @property array<string, bool>|null $notification_preferences
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'jabatan',
        'supervisor_type',
        'sekolah_id',
        'is_active',
        'notification_preferences',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'supervisor_type' => SupervisorType::class,
            'notification_preferences' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    /**
     * @return HasMany<SupervisorAssignment, $this>
     */
    public function guruAssignments(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class, 'guru_id');
    }

    /**
     * @return HasMany<SupervisorAssignment, $this>
     */
    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class, 'supervisor_id');
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * dinas_id efektif: dari sekolah (guru/supervisor) atau dari penugasan
     * Admin Dinas. Null untuk Admin Sistem.
     */
    public function resolveDinasId(): ?string
    {
        if ($this->sekolah_id !== null) {
            $dinasId = $this->relationLoaded('sekolah')
                ? $this->sekolah?->dinas_id
                : $this->sekolah()->value('dinas_id');

            return $dinasId === null ? null : (string) $dinasId;
        }

        return $this->adminDinasId();
    }
}
