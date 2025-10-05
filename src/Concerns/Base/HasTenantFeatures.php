<?php

namespace Wallo\FilamentTenants\Concerns\Base;

use Closure;
use Wallo\FilamentTenants\HasTenants;
use Wallo\FilamentTenants\Http\Livewire\TenantEmployeeManager;
use Wallo\FilamentTenants\Http\Livewire\DeleteTenantForm;
use Wallo\FilamentTenants\Http\Livewire\UpdateTenantNameForm;

trait HasTenantFeatures
{
    /**
     * The event listener to register.
     */
    protected static bool $switchesCurrentTenant = false;

    /**
     * Determine if the tenant is supporting tenant features.
     */
    public static bool $hasTenantFeatures = false;

    /**
     * Determine if invitations are sent to tenant employees.
     */
    public static bool $sendsTenantInvitations = false;

    /**
     * Determine if the application can update tenant information.
     */
    public static bool $canUpdateTenantInformation = false;

    /**
     * Determine if the application can manage tenant employees.
     */
    public static bool $canManageTenantEmployees = false;

    /**
     * Determine if the application has tenant deletion features.
     */
    public static bool $hasTenantDeletionFeatures = false;

    /**
     * The component that should be used when displaying the "Update Tenant Name" form.
     */
    public static string $updateTenantNameForm = UpdateTenantNameForm::class;

    /**
     * The component that should be used when displaying the "Tenant Employee Manager" form.
     */
    public static string $tenantEmployeeManagerForm = TenantEmployeeManager::class;

    /**
     * The component that should be used when displaying the "Delete Tenant" form.
     */
    public static string $deleteTenantForm = DeleteTenantForm::class;

    /**
     * Determine if the application supports switching current tenant.
     */
    public function switchCurrentTenant(bool $condition = true): static
    {
        static::$switchesCurrentTenant = $condition;

        return $this;
    }

    /**
     * Determine if the tenant is supporting tenant features.
     */
    public function tenants(bool | Closure | null $condition = true, bool $invitations = false): static
    {
        static::$hasTenantFeatures = $condition instanceof Closure ? $condition() : $condition;
        static::$sendsTenantInvitations = $invitations;

        return $this;
    }

    /**
     * Determine if the application supports updating tenant information.
     */
    public function updateTenantInformation(bool | Closure | null $condition = true, $component = UpdateTenantNameForm::class, int $sort = 0): static
    {
        static::$canUpdateTenantInformation = $condition instanceof Closure ? $condition() : $condition;
        static::$updateTenantNameForm = $component;
        static::$tenantComponentSortOrder[$component] = $sort;

        return $this;
    }

    /**
     * Determine if the application supports managing tenant employees.
     */
    public function manageTenantEmployees(bool | Closure | null $condition = true, $component = TenantEmployeeManager::class, int $sort = 1): static
    {
        static::$canManageTenantEmployees = $condition instanceof Closure ? $condition() : $condition;
        static::$tenantEmployeeManagerForm = $component;
        static::$tenantComponentSortOrder[$component] = $sort;

        return $this;
    }

    /**
     * Determine if the application supports tenant deletion.
     */
    public function tenantDeletion(bool | Closure | null $condition = true, $component = DeleteTenantForm::class, int $sort = 2): static
    {
        static::$hasTenantDeletionFeatures = $condition instanceof Closure ? $condition() : $condition;
        static::$deleteTenantForm = $component;
        static::$tenantComponentSortOrder[$component] = $sort;

        return $this;
    }

    /**
     * Get the feature specific components.
     */
    public static function getBaseTenantComponents(): array
    {
        $components = [];

        if (static::canUpdateTenantInformation()) {
            $components[] = static::getUpdateTenantNameForm();
        }

        if (static::canManageTenantEmployees()) {
            $components[] = static::getTenantEmployeeManagerForm();
        }

        if (static::hasTenantDeletionFeatures()) {
            $components[] = static::getDeleteTenantForm();
        }

        return $components;
    }

    /**
     * Determine if the application switches the current tenant.
     */
    public static function switchesCurrentTenant(): bool
    {
        return static::$switchesCurrentTenant;
    }

    /**
     * Determine if Tenant is supporting tenant features.
     */
    public static function hasTenantFeatures(): bool
    {
        return static::$hasTenantFeatures;
    }

    /**
     * Determine if invitations are sent to tenant employees.
     */
    public static function sendsTenantInvitations(): bool
    {
        return static::hasTenantFeatures() && static::$sendsTenantInvitations;
    }

    /**
     * Determine if a given user model utilizes the "HasTenants" trait.
     */
    public static function userHasTenantFeatures(mixed $user): bool
    {
        return (array_key_exists(HasTenants::class, class_uses_recursive($user)) ||
                method_exists($user, 'currentTenant')) &&
            static::hasTenantFeatures();
    }

    /**
     * Determine if the application can update tenant information.
     */
    public static function canUpdateTenantInformation(): bool
    {
        return static::$canUpdateTenantInformation;
    }

    /**
     * Determine if the application can manage tenant employees.
     */
    public static function canManageTenantEmployees(): bool
    {
        return static::$canManageTenantEmployees;
    }

    /**
     * Determine if the application has tenant deletion features.
     */
    public static function hasTenantDeletionFeatures(): bool
    {
        return static::$hasTenantDeletionFeatures;
    }

    /**
     * Get the component that should be used when displaying the "Update Tenant Name" form.
     */
    public static function getUpdateTenantNameForm(): string
    {
        return static::$updateTenantNameForm;
    }

    /**
     * Get the component that should be used when displaying the "Tenant Employee Manager" form.
     */
    public static function getTenantEmployeeManagerForm(): string
    {
        return static::$tenantEmployeeManagerForm;
    }

    /**
     * Get the component that should be used when displaying the "Delete Tenant" form.
     */
    public static function getDeleteTenantForm(): string
    {
        return static::$deleteTenantForm;
    }
}
