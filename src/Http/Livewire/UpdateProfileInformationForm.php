<?php

namespace Wallo\FilamentTenants\Http\Livewire;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Wallo\FilamentTenants\Contracts\UpdatesUserProfileInformation;
use Wallo\FilamentTenants\FilamentTenants;

class UpdateProfileInformationForm extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    public ?Authenticatable $user = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $user = $this->getUser();

        if ($user === null) {
            return;
        }

        $attributes = $user->withoutRelations()->getAttributes();

        $this->form->fill([
            'name' => $attributes['name'] ?? ($user->name ?? null),
            'email' => $user->email,
            'photo' => $attributes['profile_photo_path'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Flex::make([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->label(__('filament-tenants::default.fields.name'))
                                ->required(),
                            TextInput::make('email')
                                ->label(__('filament-tenants::default.fields.email'))
                                ->email()
                                ->required()
                                ->disabled(fn (): bool => ! auth()->user()?->isSuperAdmin())
                                ->dehydrated(fn (): bool => auth()->user()?->isSuperAdmin())
                                ->columnSpanFull(),
                        ]),
                    Section::make([
                        FileUpload::make('photo')
                            ->label(__('filament-tenants::default.labels.photo'))
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->storeFiles(false)
                            ->disk(FilamentTenants::profilePhotoDisk())
                            ->directory(FilamentTenants::profilePhotoStoragePath()),
                    ])
                        ->grow(false)
                        ->visible(fn (): bool => FilamentTenants::managesProfilePhotos()),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function updateProfileInformation(UpdatesUserProfileInformation $updater): void
    {
        $this->resetErrorBag();

        $user = $this->getUser();

        if ($user === null) {
            return;
        }

        $updater->update($user, $this->form->getState());

        if (FilamentTenants::hasNotificationsFeature()) {
            if (method_exists($updater, 'profileInformationUpdated')) {
                $updater->profileInformationUpdated($user, $this->data ?? []);
            } else {
                $this->profileInformationUpdated();
            }
        }
    }

    protected function profileInformationUpdated(): void
    {
        Notification::make()
            ->title(__('filament-tenants::default.notifications.profile_information_updated.title'))
            ->success()
            ->body(__('filament-tenants::default.notifications.profile_information_updated.body'))
            ->send();
    }

    protected function getUser(): ?Authenticatable
    {
        return $this->user ?? Auth::user();
    }

    public function render(): View
    {
        return view('filament-tenants::profile.update-profile-information-form');
    }
}
