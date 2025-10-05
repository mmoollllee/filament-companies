<?php

namespace Wallo\FilamentTenants\Http\Responses\Auth;

use Filament\Auth\Http\Responses\RegistrationResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Wallo\FilamentTenants\FilamentTenants;

class FilamentTenantsRegistrationResponse extends RegistrationResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Filament::auth()->user();

        if (
            FilamentTenants::autoAcceptsInvitations() &&
            method_exists($user, 'hasAnyTenants') &&
            ! $user->hasAnyTenants() &&
            ($invitation = FilamentTenants::tenantInvitationModel()::where('email', $user->email)->first())
        ) {
            return redirect(FilamentTenants::generateAcceptInvitationUrl($invitation));
        }

        return parent::toResponse($request);
    }
}
