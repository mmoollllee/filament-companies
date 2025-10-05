@if (Wallo\FilamentTenants\FilamentTenants::hasSocialiteFeatures())
    <x-filament-tenants::socialite :error-message="$errors->first('filament-tenants')" />
@endif
