<x-filament-panels::page>
    @php
        $components = \Wallo\FilamentTenants\FilamentTenants::getTenantComponents();
        $deleteTenantForm = \Wallo\FilamentTenants\FilamentTenants::getDeleteTenantForm();
    @endphp

    <div class="space-y-6">
        @foreach($components as $component)
            @if($component === $deleteTenantForm)
                @if (! $tenant->personal_tenant && Gate::check('delete', $tenant))
                    @livewire($component, compact('tenant'))
                @endif
            @else
                @livewire($component, compact('tenant'))
            @endif
        @endforeach
    </div>
</x-filament-panels::page>

