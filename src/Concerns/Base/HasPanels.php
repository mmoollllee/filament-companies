<?php

namespace Wallo\FilamentTenants\Concerns\Base;

use LogicException;

trait HasPanels
{
    /**
     * The user panel.
     */
    protected static string $userPanel;

    /**
     * The tenant panel.
     */
    protected static string $tenantPanel;

    /**
     * Set the user panel.
     */
    public function userPanel(string $panel): static
    {
        static::$userPanel = $panel;

        return $this;
    }

    /**
     * Get the user panel configuration.
     */
    public static function getUserPanel(): string
    {
        if (! isset(static::$userPanel)) {
            throw new LogicException('FilamentTenants plugin has not been configured with a user panel.');
        }

        return static::$userPanel;
    }

    /**
     * Determine if the user panel is set.
     */
    public static function hasUserPanel(): bool
    {
        return isset(static::$userPanel);
    }

    /**
     * Get the panel where the plugin is registered (The tenant panel).
     */
    public static function getTenantPanel(): string
    {
        if (! isset(static::$tenantPanel)) {
            throw new LogicException('FilamentTenants plugin has not been registered to any panel.');
        }

        return static::$tenantPanel;
    }
}
