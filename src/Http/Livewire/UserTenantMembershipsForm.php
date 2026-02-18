<?php

namespace Wallo\FilamentTenants\Http\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Wallo\FilamentTenants\Contracts\AddsTenantEmployees;
use Wallo\FilamentTenants\Contracts\InvitesTenantEmployees;
use Wallo\FilamentTenants\Contracts\RemovesTenantEmployees;
use Wallo\FilamentTenants\FilamentTenants;

class UserTenantMembershipsForm extends Component
{
    /**
     * The profile user context. Can be injected by parent page.
     */
    public mixed $user = null;

    /**
     * Manual tenant assignment form state.
     *
     * @var array{tenant_id: int|null, role: string|null, mode: 'invite'|'direct'}
     */
    public array $assignTenantForm = [
        'tenant_id' => null,
        'role' => null,
        'mode' => 'invite',
    ];

    /**
     * The tenant ID currently selected for member removal.
     */
    public ?int $tenantIdBeingRemoved = null;

    public function mount(): void
    {
        $this->assignTenantForm['mode'] = FilamentTenants::sendsTenantInvitations() ? 'invite' : 'direct';

        if (FilamentTenants::hasRoles()) {
            $defaultRole = array_key_first(FilamentTenants::$roles);
            $this->assignTenantForm['role'] = is_string($defaultRole) ? $defaultRole : null;
        }
    }

    /**
     * Get the current user of the application.
     */
    #[Computed]
    public function currentUser(): ?Authenticatable
    {
        return Auth::user();
    }

    /**
     * Get the profile user the section should render for.
     */
    #[Computed]
    public function profileUser(): ?Authenticatable
    {
        return $this->user ?? Auth::user();
    }

    /**
     * Get all tenants the current user belongs to.
     *
     * @return Collection<int, array{
     *     tenant_id: int,
     *     name: string,
     *     relationship: string,
     *     role: string|null,
     *     invitation_id: int|null,
     *     can_accept_invitation: bool,
     *     can_remove: bool,
     *     can_cancel_invitation: bool
     * }>
     */
    #[Computed]
    public function memberTenants(): Collection
    {
        $user = $this->profileUser;

        if ($user === null || ! method_exists($user, 'allTenants')) {
            return collect();
        }

        $user->loadMissing(['ownedTenants', 'tenants']);

        return $user->allTenants()
            ->unique('id')
            ->values()
            ->map(function ($tenant) use ($user) {
                $isOwner = (int) ($tenant->user_id ?? 0) === (int) $user->getAuthIdentifier();

                return [
                    'tenant_id' => (int) $tenant->id,
                    'name' => (string) $tenant->name,
                    'relationship' => $isOwner
                        ? __('filament-tenants::default.labels.owner')
                        : __('filament-tenants::default.labels.member'),
                    'role' => $isOwner ? null : $this->resolveRoleName($tenant->employeeship->role ?? null),
                    'invitation_id' => null,
                    'can_accept_invitation' => false,
                    'can_remove' => ! $isOwner && $this->canRemoveTenantEmployee($tenant),
                    'can_cancel_invitation' => false,
                ];
            });
    }

    /**
     * Get all pending tenant invitations for the current user.
     *
     * @return Collection<int, array{
     *     tenant_id: int,
     *     name: string,
     *     relationship: string,
     *     role: string|null,
     *     invitation_id: int|null,
     *     can_accept_invitation: bool,
     *     can_remove: bool,
     *     can_cancel_invitation: bool
     * }>
     */
    #[Computed]
    public function invitations(): Collection
    {
        $user = $this->profileUser;

        if ($user === null || ! filled($user->email)) {
            return collect();
        }

        $memberTenantIds = $this->memberTenants->pluck('tenant_id')->all();

        $invitationModel = FilamentTenants::tenantInvitationModel();

        return $invitationModel::query()
            ->where('email', $user->email)
            ->with('tenant')
            ->get()
            ->filter(static function ($invitation) use ($memberTenantIds) {
                if ($invitation->tenant === null) {
                    return false;
                }

                return ! in_array((int) $invitation->tenant_id, $memberTenantIds, true);
            })
            ->values()
            ->map(function ($invitation) {
                return [
                    'tenant_id' => (int) $invitation->tenant_id,
                    'name' => (string) $invitation->tenant->name,
                    'relationship' => __('filament-tenants::default.labels.invited'),
                    'role' => $this->resolveRoleName($invitation->role),
                    'invitation_id' => (int) $invitation->id,
                    'can_accept_invitation' => $this->canAcceptInvitation($invitation->tenant),
                    'can_remove' => false,
                    'can_cancel_invitation' => $this->canRemoveTenantEmployee($invitation->tenant),
                ];
            });
    }

    /**
     * Get a merged and sorted tenant + invitation list.
     *
     * @return Collection<int, array{
     *     tenant_id: int,
     *     name: string,
     *     relationship: string,
     *     role: string|null,
     *     invitation_id: int|null,
     *     can_accept_invitation: bool,
     *     can_remove: bool,
     *     can_cancel_invitation: bool
     * }>
     */
    #[Computed]
    public function tenantList(): Collection
    {
        return $this->memberTenants
            ->merge($this->invitations)
            ->sortBy('name')
            ->values();
    }

    /**
     * Get the tenants the current user can assign the profile user to.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    #[Computed]
    public function assignableTenants(): Collection
    {
        $currentUser = $this->currentUser;
        $profileUser = $this->profileUser;

        if ($currentUser === null || $profileUser === null || ! filled($profileUser->email)) {
            return collect();
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $tenantModel */
        $tenantModel = FilamentTenants::tenantModel();

        $existingTenantIds = $this->tenantList
            ->pluck('tenant_id')
            ->map(static fn (mixed $id) => (int) $id)
            ->values()
            ->all();

        return $tenantModel::query()
            ->get()
            ->filter(fn ($tenant) => Gate::forUser($currentUser)->check('addTenantEmployee', $tenant))
            ->reject(static fn ($tenant) => in_array((int) $tenant->id, $existingTenantIds, true))
            ->sortBy('name')
            ->values()
            ->map(static fn ($tenant) => [
                'id' => (int) $tenant->id,
                'name' => (string) $tenant->name,
            ]);
    }

    /**
     * Get the available tenant employee roles.
     *
     * @return array<int, array{key: string, name: string}>
     */
    #[Computed]
    public function roles(): array
    {
        return collect(FilamentTenants::$roles)
            ->map(static fn ($role) => [
                'key' => $role->key,
                'name' => $role->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Mark a pending invitation as accepted for the profile user.
     */
    public function acceptInvitation(int $invitationId, AddsTenantEmployees $adder): void
    {
        $profileUser = $this->profileUser;
        $currentUser = $this->currentUser;

        if ($profileUser === null || $currentUser === null || ! filled($profileUser->email)) {
            return;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $invitationModel */
        $invitationModel = FilamentTenants::tenantInvitationModel();

        $invitation = $invitationModel::query()
            ->with('tenant')
            ->find($invitationId);

        if ($invitation === null || $invitation->tenant === null) {
            return;
        }

        if (strcasecmp((string) $invitation->email, (string) $profileUser->email) !== 0) {
            return;
        }

        if (! $this->canAcceptInvitation($invitation->tenant)) {
            return;
        }

        if (! $invitation->tenant->hasUserWithEmail((string) $profileUser->email)) {
            $adder->add(
                $currentUser,
                $invitation->tenant,
                (string) $profileUser->email,
                $invitation->role
            );
        }

        $invitation->delete();
    }

    /**
     * Cancel a pending invitation for the profile user.
     */
    public function cancelInvitation(int $invitationId): void
    {
        $profileUser = $this->profileUser;
        $currentUser = $this->currentUser;

        if ($profileUser === null || $currentUser === null || ! filled($profileUser->email)) {
            return;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $invitationModel */
        $invitationModel = FilamentTenants::tenantInvitationModel();

        $invitation = $invitationModel::query()
            ->with('tenant')
            ->find($invitationId);

        if ($invitation === null || $invitation->tenant === null) {
            return;
        }

        if (strcasecmp((string) $invitation->email, (string) $profileUser->email) !== 0) {
            return;
        }

        if (! $this->canRemoveTenantEmployee($invitation->tenant)) {
            return;
        }

        $invitation->delete();
    }

    /**
     * Assign the profile user to a tenant by invitation or direct add.
     */
    public function assignTenant(InvitesTenantEmployees $inviter, AddsTenantEmployees $adder): void
    {
        $this->resetErrorBag();

        $profileUser = $this->profileUser;
        $currentUser = $this->currentUser;

        if ($profileUser === null || $currentUser === null || ! filled($profileUser->email)) {
            return;
        }

        $tenantId = $this->assignTenantForm['tenant_id'];

        if (! filled($tenantId)) {
            return;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $tenantModel */
        $tenantModel = FilamentTenants::tenantModel();
        $tenant = $tenantModel::query()->find($tenantId);

        if ($tenant === null || ! $currentUser->can('addTenantEmployee', $tenant)) {
            return;
        }

        $mode = $this->assignTenantForm['mode'] === 'direct' ? 'direct' : 'invite';
        $role = FilamentTenants::hasRoles() ? $this->assignTenantForm['role'] : null;
        $email = (string) $profileUser->email;

        if ($mode === 'invite') {
            $inviter->invite($currentUser, $tenant, $email, $role);
            $this->tenantInvitationSent($email, (string) $tenant->name);
        } else {
            $adder->add($currentUser, $tenant, $email, $role);
            $this->tenantAssigned($email, (string) $tenant->name);
        }

        $this->assignTenantForm['tenant_id'] = null;

        $this->dispatch('close-modal', id: 'assigningTenant');
    }

    /**
     * Cancel tenant assignment and close the modal.
     */
    public function cancelTenantAssignment(): void
    {
        $this->resetErrorBag();
        $this->dispatch('close-modal', id: 'assigningTenant');
    }

    /**
     * Confirm that the profile user should be removed from a tenant.
     */
    public function confirmTenantRemoval(int $tenantId): void
    {
        $tenant = $this->memberTenants->first(function (array $item) use ($tenantId): bool {
            return $item['tenant_id'] === $tenantId && ($item['can_remove'] ?? false);
        });

        if ($tenant === null) {
            return;
        }

        $this->tenantIdBeingRemoved = $tenantId;
        $this->dispatch('open-modal', id: 'confirmingTenantRemoval');
    }

    /**
     * Remove the profile user from the selected tenant.
     */
    public function removeTenantMembership(RemovesTenantEmployees $remover): void
    {
        $profileUser = $this->profileUser;
        $currentUser = $this->currentUser;
        $tenantId = $this->tenantIdBeingRemoved;

        if ($profileUser === null || $currentUser === null || $tenantId === null) {
            return;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $tenantModel */
        $tenantModel = FilamentTenants::tenantModel();
        $tenant = $tenantModel::query()->find($tenantId);

        if ($tenant === null || ! $this->canRemoveTenantEmployee($tenant)) {
            $this->cancelTenantRemoval();

            return;
        }

        if ((int) ($tenant->user_id ?? 0) === (int) $profileUser->getAuthIdentifier()) {
            $this->cancelTenantRemoval();

            return;
        }

        if (method_exists($tenant, 'hasUser') && ! $tenant->hasUser($profileUser)) {
            $this->cancelTenantRemoval();

            return;
        }

        $remover->remove($currentUser, $tenant, $profileUser);

        $this->tenantIdBeingRemoved = null;
        $this->dispatch('close-modal', id: 'confirmingTenantRemoval');
        $this->tenantMembershipRemoved((string) $profileUser->email, (string) $tenant->name);
    }

    /**
     * Cancel tenant member removal and close the modal.
     */
    public function cancelTenantRemoval(): void
    {
        $this->tenantIdBeingRemoved = null;
        $this->dispatch('close-modal', id: 'confirmingTenantRemoval');
    }

    protected function resolveRoleName(?string $roleKey): ?string
    {
        if (! filled($roleKey)) {
            return null;
        }

        return FilamentTenants::findRole($roleKey)?->name ?? Str::of($roleKey)->headline()->toString();
    }

    protected function canAcceptInvitation(mixed $tenant): bool
    {
        $currentUser = $this->currentUser;
        $profileUser = $this->profileUser;

        if ($currentUser === null || $profileUser === null || ! filled($profileUser->email)) {
            return false;
        }

        return Gate::forUser($currentUser)->check('addTenantEmployee', $tenant);
    }

    protected function canRemoveTenantEmployee(mixed $tenant): bool
    {
        $currentUser = $this->currentUser;
        $profileUser = $this->profileUser;

        if ($currentUser === null || $profileUser === null) {
            return false;
        }

        return Gate::forUser($currentUser)->check('removeTenantEmployee', $tenant);
    }

    protected function tenantInvitationSent(string $email, string $tenant): void
    {
        if (! FilamentTenants::hasNotificationsFeature()) {
            return;
        }

        Notification::make()
            ->title(__('filament-tenants::default.notifications.tenant_invitation_sent.title'))
            ->success()
            ->body(Str::inlineMarkdown(__('filament-tenants::default.notifications.tenant_invitation_sent_by_admin.body', compact('email', 'tenant'))))
            ->send();
    }

    protected function tenantAssigned(string $email, string $tenant): void
    {
        if (! FilamentTenants::hasNotificationsFeature()) {
            return;
        }

        Notification::make()
            ->title(__('filament-tenants::default.notifications.tenant_assigned.title'))
            ->success()
            ->body(Str::inlineMarkdown(__('filament-tenants::default.notifications.tenant_assigned.body', compact('email', 'tenant'))))
            ->send();
    }

    protected function tenantMembershipRemoved(string $email, string $tenant): void
    {
        if (! FilamentTenants::hasNotificationsFeature()) {
            return;
        }

        Notification::make()
            ->title(__('filament-tenants::default.notifications.tenant_removed.title'))
            ->success()
            ->body(Str::inlineMarkdown(__('filament-tenants::default.notifications.tenant_removed.body', compact('email', 'tenant'))))
            ->send();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('filament-tenants::profile.user-tenant-memberships-form');
    }
}
