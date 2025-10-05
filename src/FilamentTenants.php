<?php

namespace Wallo\FilamentTenants;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Contracts\Plugin;
use Filament\Events\TenantSet;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Wallo\FilamentTenants\Contracts\CreatesConnectedAccounts;
use Wallo\FilamentTenants\Contracts\CreatesUserFromProvider;
use Wallo\FilamentTenants\Contracts\HandlesInvalidState;
use Wallo\FilamentTenants\Contracts\UpdatesConnectedAccounts;
use Wallo\FilamentTenants\Http\Controllers\OAuthController;
use Wallo\FilamentTenants\Http\Responses\Auth\FilamentTenantsRegistrationResponse;
use Wallo\FilamentTenants\Listeners\SwitchCurrentTenant;
use Wallo\FilamentTenants\Pages\Tenant\TenantSettings;
use Wallo\FilamentTenants\Pages\Tenant\CreateTenant;

class FilamentTenants implements Plugin
{
    use Concerns\Base\HasAddedTenantComponents;
    use Concerns\Base\HasAddedProfileComponents;
    use Concerns\Base\HasAutoAcceptInvitations;
    use Concerns\Base\HasBaseActionBindings;
    use Concerns\Base\HasBaseModels;
    use Concerns\Base\HasBaseProfileComponents;
    use Concerns\Base\HasBaseProfileFeatures;
    use Concerns\Base\HasTenantFeatures;
    use Concerns\Base\HasModals;
    use Concerns\Base\HasNotifications;
    use Concerns\Base\HasPanels;
    use Concerns\Base\HasPermissions;
    use Concerns\Base\HasRoutes;
    use Concerns\Base\HasTermsAndPrivacyPolicy;
    use Concerns\ManagesTenantComponents;
    use Concerns\ManagesProfileComponents;
    use Concerns\Socialite\CanEnableSocialite;
    use Concerns\Socialite\HasConnectedAccountModel;
    use Concerns\Socialite\HasProviderFeatures;
    use Concerns\Socialite\HasProviders;
    use Concerns\Socialite\HasSocialiteActionBindings;
    use Concerns\Socialite\HasSocialiteComponents;
    use Concerns\Socialite\HasSocialiteProfileFeatures;

    public function getId(): string
    {
        return 'tenants';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        static::$tenantPanel = $panel->getId();
        if (static::hasTenantFeatures()) {
            Livewire::component('filament.pages.tenants.create_tenant', CreateTenant::class);
            Livewire::component('filament.pages.tenants.tenant_settings', TenantSettings::class);
        }

        app()->bind(RegistrationResponseContract::class, FilamentTenantsRegistrationResponse::class);

        if (static::hasSocialiteFeatures()) {
            app()->bind(OAuthController::class, static function (Application $app) {
                return new OAuthController(
                    $app->make(CreatesUserFromProvider::class),
                    $app->make(CreatesConnectedAccounts::class),
                    $app->make(UpdatesConnectedAccounts::class),
                    $app->make(HandlesInvalidState::class),
                );
            });
        }

        if (static::$registersRoutes) {
            $panel->routes(fn () => $this->registerPublicRoutes());
            $panel->authenticatedRoutes(fn () => $this->registerAuthenticatedRoutes());
        }
    }

    public function boot(Panel $panel): void
    {
        if (static::switchesCurrentTenant()) {
            Event::listen(TenantSet::class, SwitchCurrentTenant::class);
        }

        if (static::hasSocialiteFeatures()) {
            $this->registerSocialiteRenderHooks();
        }
    }

    protected function registerSocialiteRenderHooks(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): View => view('filament-tenants::components.socialite-login'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
            fn (): View => view('filament-tenants::components.socialite-login'),
        );
    }
}
