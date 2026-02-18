<?php

namespace Wallo\FilamentTenants\Http\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Wallo\FilamentTenants\Contracts\UpdatesUserPasswords;
use Wallo\FilamentTenants\FilamentTenants;

class UpdatePasswordForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?Authenticatable $user = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label(__('filament-tenants::default.fields.current_password'))
                    ->password()
                    ->revealable()
                    ->required(),
                TextInput::make('password')
                    ->label(__('filament-tenants::default.labels.new_password'))
                    ->password()
                    ->revealable()
                    ->confirmed()
                    ->required(),
                TextInput::make('password_confirmation')
                    ->label(__('filament-tenants::default.labels.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function updatePassword(UpdatesUserPasswords $updater): void
    {
        $this->resetErrorBag();

        $user = $this->getUser();

        if ($user === null) {
            return;
        }

        $updater->update($user, $this->form->getState());

        if ($user->is(Auth::user())) {
            session()->put([
                'password_hash_' . Auth::getDefaultDriver() => $user->getAuthPassword(),
            ]);
        }

        $this->form->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        if (FilamentTenants::hasNotificationsFeature()) {
            if (method_exists($updater, 'passwordUpdated')) {
                $updater->passwordUpdated($user, $this->data ?? []);
            } else {
                $this->passwordUpdated();
            }
        }
    }

    protected function getUser(): ?Authenticatable
    {
        return $this->user ?? Auth::user();
    }

    public function render(): View
    {
        return view('filament-tenants::profile.update-password-form');
    }

    public function passwordUpdated(): void
    {
        Notification::make()
            ->title(__('filament-tenants::default.notifications.password_updated.title'))
            ->success()
            ->body(__('filament-tenants::default.notifications.password_updated.body'))
            ->send();
    }
}
