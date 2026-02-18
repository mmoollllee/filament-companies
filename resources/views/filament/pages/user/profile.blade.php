<x-filament-panels::page>
    @php
        $components = \Wallo\FilamentTenants\FilamentTenants::getProfileComponents();
        $user = $this->record ?? auth()->user();
    @endphp

    @foreach($components as $index => $component)
        @php
            $componentKey = $component . '-' . ($user?->getAuthIdentifier() ?? 'current');
            $acceptsUser = class_exists($component) && property_exists($component, 'user');
        @endphp

        @if ($acceptsUser)
            @livewire($component, ['user' => $user], key($componentKey))
        @else
            @livewire($component)
        @endif
    @endforeach
</x-filament-panels::page>
