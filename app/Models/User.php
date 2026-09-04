<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role_id', 'telegram_chat_id', 'telegram_notifications_enabled', 'tax_code', 'address_street', 'address_zip', 'address_city', 'payment_beneficiary', 'payment_iban', 'payment_bic', 'payment_enabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->role_id !== null && ! $user->isDirty('telegram_notifications_enabled')) {
                $user->telegram_notifications_enabled = (bool) Role::whereKey($user->role_id)
                    ->value('telegram_notifications_enabled');
            }
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('role_id')) {
                $user->telegram_notifications_enabled = $user->role_id !== null
                    && (bool) Role::whereKey($user->role_id)->value('telegram_notifications_enabled');
            }
        });

        static::saved(function (User $user): void {
            if ($user->payment_enabled && $user->role?->name === 'host_owner') {
                static::query()
                    ->whereKeyNot($user->id)
                    ->where('payment_enabled', true)
                    ->update(['payment_enabled' => false]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'telegram_notifications_enabled' => 'boolean',
            'payment_enabled' => 'boolean',
        ];
    }

    public static function paymentOwner(): ?self
    {
        return static::query()
            ->where('payment_enabled', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'host_owner'))
            ->orderBy('id')
            ->first();
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * All pivot records for this user (both grants and denials).
     * Use this relationship for syncing.
     */
    public function userPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('denied');
    }

    /**
     * Per-user permission grants (denied = false).
     * These add permissions on top of the role.
     */
    public function permissionOverrides(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->wherePivot('denied', false);
    }

    /**
     * Per-user permission denials (denied = true).
     * These remove permissions granted by the role.
     */
    public function permissionDenials(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->wherePivot('denied', true);
    }

    // ── Authorization helpers ──────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    /**
     * Check whether this user has a given permission slug.
     * Super admin always returns true.
     * manage_users is non-delegable: only super_admin can have it.
     * Explicit per-user denials override role grants.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // manage_users is non-delegable via per-user overrides
        if ($permission === 'manage_users') {
            return false;
        }

        // Explicit denial overrides everything
        if ($this->permissionDenials->contains('name', $permission)) {
            return false;
        }

        // Explicit grant
        if ($this->permissionOverrides->contains('name', $permission)) {
            return true;
        }

        // Role grant
        return $this->role && $this->role->permissions->contains('name', $permission);
    }
}
