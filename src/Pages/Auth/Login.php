<?php

namespace Wallo\FilamentTenants\Pages\Auth;

use Filament\Schemas\Schema;
use Wallo\FilamentTenants\FilamentTenants;

class Login extends \Filament\Auth\Pages\Login
{

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data')
            ->model(FilamentTenants::userModel());
    }
}
