@php
    $modals = \Wallo\FilamentTenants\FilamentTenants::getModals();
@endphp

<x-filament-tenants::grid-section md="2">
    <x-slot name="title">
        {{ __('filament-tenants::default.grid_section_titles.my_tenants') }}
    </x-slot>

    <x-slot name="description">
        {{ __('filament-tenants::default.grid_section_descriptions.my_tenants') }}
    </x-slot>

    <x-filament::section>
        @if ($this->assignableTenants->isNotEmpty())
            <x-filament::modal
                id="assigningTenant"
                icon="heroicon-o-user-plus"
                icon-color="primary"
                alignment="{{ $modals['alignment'] }}"
                footer-actions-alignment="{{ $modals['formActionsAlignment'] }}"
                width="{{ $modals['width'] }}"
            >
                <x-slot name="trigger">
                    <div class="text-left">
                        <x-filament::button>
                            {{ __('filament-tenants::default.buttons.assign_tenant') }}
                        </x-filament::button>
                    </div>
                </x-slot>

                <x-slot name="heading">
                    {{ __('filament-tenants::default.modal_titles.assign_tenant') }}
                </x-slot>

                <x-slot name="description">
                    {{ __('filament-tenants::default.modal_descriptions.assign_tenant') }}
                </x-slot>

                <div class="space-y-4">
                    <x-filament-forms::field-wrapper
                        id="tenant_id"
                        statePath="assignTenantForm.tenant_id"
                        required="required"
                        label="{{ __('filament-tenants::default.labels.tenant') }}"
                    >
                        <x-filament::input.wrapper class="overflow-hidden">
                            <x-filament::input.select id="tenant_id" wire:model="assignTenantForm.tenant_id">
                                <option value="">{{ __('filament-tenants::default.labels.select_tenant') }}</option>
                                @foreach ($this->assignableTenants as $tenant)
                                    <option value="{{ $tenant['id'] }}">{{ $tenant['name'] }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </x-filament-forms::field-wrapper>

                    @if (count($this->roles) > 0)
                        <x-filament-forms::field-wrapper
                        id="role"
                        statePath="assignTenantForm.role"
                        required="required"
                        label="{{ __('filament-tenants::default.labels.role') }}"
                    >
                        <x-filament::input.wrapper class="overflow-hidden">
                            <x-filament::input.select id="role" wire:model="assignTenantForm.role">
                                @foreach ($this->roles as $role)
                                    <option value="{{ $role['key'] }}">{{ $role['name'] }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        </x-filament-forms::field-wrapper>
                    @endif

                    <x-filament-forms::field-wrapper
                        id="assignment_mode"
                        statePath="assignTenantForm.mode"
                        label="{{ __('filament-tenants::default.labels.assignment_mode') }}"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                @class([
                                    'text-sm',
                                    'font-semibold text-gray-900 dark:text-gray-100' => ($assignTenantForm['mode'] ?? 'invite') === 'direct',
                                    'text-gray-500 dark:text-gray-400' => ($assignTenantForm['mode'] ?? 'invite') !== 'direct',
                                ])
                            >
                                {{ __('filament-tenants::default.labels.add_directly') }}
                            </span>

                            <button
                                type="button"
                                role="switch"
                                aria-checked="{{ ($assignTenantForm['mode'] ?? 'invite') === 'invite' ? 'true' : 'false' }}"
                                wire:click="$set('assignTenantForm.mode', '{{ ($assignTenantForm['mode'] ?? 'invite') === 'invite' ? 'direct' : 'invite' }}')"
                                @class([
                                    'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border border-transparent transition focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800',
                                    'bg-primary-600' => ($assignTenantForm['mode'] ?? 'invite') === 'invite',
                                    'bg-gray-300 dark:bg-gray-600' => ($assignTenantForm['mode'] ?? 'invite') !== 'invite',
                                ])
                            >
                                <span class="sr-only">{{ __('filament-tenants::default.labels.assignment_mode') }}</span>
                                <span
                                    @class([
                                        'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-sm ring-0 transition',
                                        'translate-x-5' => ($assignTenantForm['mode'] ?? 'invite') === 'invite',
                                        'translate-x-0.5' => ($assignTenantForm['mode'] ?? 'invite') !== 'invite',
                                    ])
                                ></span>
                            </button>

                            <span
                                @class([
                                    'text-sm',
                                    'font-semibold text-gray-900 dark:text-gray-100' => ($assignTenantForm['mode'] ?? 'invite') === 'invite',
                                    'text-gray-500 dark:text-gray-400' => ($assignTenantForm['mode'] ?? 'invite') !== 'invite',
                                ])
                            >
                                {{ __('filament-tenants::default.labels.send_invitation') }}
                            </span>
                        </div>
                    </x-filament-forms::field-wrapper>
                </div>

                <x-slot name="footerActions">
                    @if ($modals['cancelButtonAction'])
                        <x-filament::button color="gray" wire:click="cancelTenantAssignment">
                            {{ __('filament-tenants::default.buttons.cancel') }}
                        </x-filament::button>
                    @endif

                    <x-filament::button wire:click="assignTenant">
                        {{ __('filament-tenants::default.buttons.assign_tenant') }}
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>

            <x-filament-tenants::section-border />
        @endif

        @if ($this->tenantList->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('filament-tenants::default.subheadings.profile.no_tenants') }}
            </p>
        @else
            <div class="overflow-x-auto rounded-xl bg-white shadow-xs dark:bg-gray-800">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-white dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                {{ __('filament-tenants::default.fields.name') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                {{ __('filament-tenants::default.labels.relationship') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                {{ __('filament-tenants::default.labels.role') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                {{ __('filament-tenants::default.labels.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($this->tenantList as $tenant)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $tenant['name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $tenant['relationship'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $tenant['role'] ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if (($tenant['invitation_id'] ?? null) !== null && ($tenant['can_accept_invitation'] ?? false))
                                            <x-filament::button size="sm" wire:click="acceptInvitation({{ $tenant['invitation_id'] }})">
                                                {{ __('filament-tenants::default.buttons.confirm') }}
                                            </x-filament::button>
                                        @endif

                                        @if (($tenant['invitation_id'] ?? null) !== null && ($tenant['can_cancel_invitation'] ?? false))
                                            <x-filament::button size="sm" color="danger" outlined="true" wire:click="cancelInvitation({{ $tenant['invitation_id'] }})">
                                                <x-heroicon-o-x-mark class="h-4 w-4" />
                                                <span class="sr-only">{{ __('filament-tenants::default.buttons.remove') }}</span>
                                            </x-filament::button>
                                        @elseif (($tenant['can_remove'] ?? false) === true)
                                            <x-filament::button size="sm" color="danger" outlined="true" wire:click="confirmTenantRemoval({{ $tenant['tenant_id'] }})">
                                                <x-heroicon-o-x-mark class="h-4 w-4" />
                                                <span class="sr-only">{{ __('filament-tenants::default.buttons.remove') }}</span>
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-filament::modal
            id="confirmingTenantRemoval"
            icon="heroicon-o-exclamation-triangle"
            icon-color="danger"
            alignment="{{ $modals['alignment'] }}"
            footer-actions-alignment="{{ $modals['formActionsAlignment'] }}"
            width="{{ $modals['width'] }}"
        >
            <x-slot name="heading">
                {{ __('filament-tenants::default.modal_titles.remove_tenant_employee') }}
            </x-slot>

            <x-slot name="description">
                {{ __('filament-tenants::default.modal_descriptions.remove_tenant_employee') }}
            </x-slot>

            <x-slot name="footerActions">
                @if($modals['cancelButtonAction'])
                    <x-filament::button color="gray" wire:click="cancelTenantRemoval">
                        {{ __('filament-tenants::default.buttons.cancel') }}
                    </x-filament::button>
                @endif

                <x-filament::button color="danger" wire:click="removeTenantMembership">
                    {{ __('filament-tenants::default.buttons.remove') }}
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    </x-filament::section>
</x-filament-tenants::grid-section>
