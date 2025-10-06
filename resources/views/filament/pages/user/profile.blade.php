<x-filament-panels::page>
    <form wire:submit="save" id="form" class="grid gap-y-1">
        {{ $this->form }}

        @if (Gate::check('update'))
            <div class="text-right">
                <x-filament::button type="submit">
                    {{ __('filament-tenants::default.buttons.save') }}
                </x-filament::button>
            </div>
        @endif
    </form>

    @php
        $components = \Wallo\FilamentTenants\FilamentTenants::getProfileComponents();
        $user = $this->record ?? auth()->user();
    @endphp

    @foreach($components as $index => $component)
        @livewire($component)
    @endforeach
</x-filament-panels::page>
