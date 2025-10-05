<?php

namespace Wallo\FilamentTenants\Concerns\Base;

trait HasAddedTenantComponents
{
    public static array $addedTenantComponents = [];

    public function addTenantComponents(array $componentsWithSortOrder): static
    {
        foreach ($componentsWithSortOrder as $sort => $component) {
            static::$addedTenantComponents[] = $component;
            static::$tenantComponentSortOrder[$component] = $sort;
        }

        return $this;
    }

    /**
     * Get the added tenant page components.
     */
    public static function getAddedTenantComponents(): array
    {
        return static::$addedTenantComponents;
    }
}
