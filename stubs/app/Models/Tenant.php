<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wallo\FilamentTenants\Tenant as FilamentTenantsTenant;
use Wallo\FilamentTenants\Events\TenantCreated;
use Wallo\FilamentTenants\Events\TenantDeleted;
use Wallo\FilamentTenants\Events\TenantUpdated;

class Tenant extends FilamentTenantsTenant implements HasAvatar
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_tenant',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TenantCreated::class,
        'updated' => TenantUpdated::class,
        'deleted' => TenantDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_tenant' => 'boolean',
        ];
    }

    public function getFilamentAvatarUrl(): string
    {
        // Reuse the tenant owner's avatar in Filament tenant switchers/lists.
        return $this->owner->profile_photo_url;
    }

    protected static function booted(): void
    {
        // Limit visible tenants to those relevant for the authenticated user.
        static::addGlobalScope('userTenants', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();

                // Optional extension point: only bypass scope when the app user model
                // provides `isSuperAdmin()` and it returns true.
                $isSuperAdmin = method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

                if (! $isSuperAdmin) {
                    // Tenant is visible when user is owner, member, or invited by email.
                    $builder->whereBelongsTo($user, 'owner')->orWhereHas('users', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })->orWhereHas('tenantInvitations', function ($query) use ($user) {
                        $query->where('email', $user->email);
                    });
                }
            }
        });
    }
}
