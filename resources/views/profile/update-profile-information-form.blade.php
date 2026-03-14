<x-filament-tenants::grid-section md="2">
    <x-slot name="title">
        {{ __('filament-tenants::default.grid_section_titles.profile_information') }}
    </x-slot>

    <x-slot name="description">
        {{ __('filament-tenants::default.grid_section_descriptions.profile_information') }}
    </x-slot>

    <x-filament::section>
        <form wire:submit="updateProfileInformation" class="fi-sc-form space-y-4">
            {{ $this->form }}

            <div class="text-left">
                <x-filament::button type="submit">
                    {{ __('filament-tenants::default.buttons.save') }}
                </x-filament::button>
            </div>
        </form>

        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-tenants::grid-section>
