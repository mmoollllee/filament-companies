<x-filament-panels::page>
@if (Gate::check('update', $tenant))
    <form id="form" wire:submit="save" class="gap-y-1">
        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit">
                {{ __('filament-tenants::default.buttons.save') }}
            </x-filament::button>
        </div>
    </form>
    
    @livewire(\Wallo\FilamentTenants\Http\Livewire\TenantEmployeeManager::class, compact('tenant'))
    
    @if (!$tenant->personal_tenant && Gate::check('delete', $tenant))
    <x-filament-tenants::section-border />
    @livewire(\Wallo\FilamentTenants\Http\Livewire\DeleteTenantForm::class, compact('tenant'))
    @endif
@endif
</x-filament-panels::page>
