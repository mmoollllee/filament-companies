<?php

namespace Wallo\FilamentTenants\Pages\Tenant;

use Filament\Facades\Filament;
use Filament\Pages\Tenancy\EditTenantProfile as BaseEditTenantProfile;

class TenantSettings extends BaseEditTenantProfile
{
    protected string $view = 'filament-tenants::filament.pages.tenants.tenant_settings';

    public static function getLabel(): string
    {
        return __('filament-tenants::default.pages.titles.tenant_settings');
    }

    protected function getViewData(): array
    {
        return [
            'tenant' => Filament::getTenant(),
        ];
    }
}
