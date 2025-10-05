<?php

namespace Wallo\FilamentTenants\Concerns;

trait ManagesTenantComponents
{
    public static array $tenantComponentSortOrder = [];

    /**
     * Get the tenant page components.
     */
    public static function getTenantComponents(): array
    {
        $featureComponents = static::getBaseTenantComponents();
        $addedComponents = static::getAddedTenantComponents();

        $components = [...$featureComponents, ...$addedComponents];

        usort($components, static function ($a, $b) {
            return static::$tenantComponentSortOrder[$a] <=> static::$tenantComponentSortOrder[$b];
        });

        return $components;
    }
}
