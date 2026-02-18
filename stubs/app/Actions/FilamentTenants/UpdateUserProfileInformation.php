<?php

namespace App\Actions\FilamentTenants;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Wallo\FilamentTenants\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $photo = $input['photo'] ?? null;

        $photoRules = ['nullable'];

        if ($photo instanceof UploadedFile || $photo instanceof TemporaryUploadedFile) {
            $photoRules[] = 'mimes:jpg,jpeg,png';
            $photoRules[] = 'max:1024';
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => $photoRules,
        ])->validateWithBag('updateProfileInformation');

        if ($photo instanceof UploadedFile || $photo instanceof TemporaryUploadedFile) {
            $user->updateProfilePhoto($photo);
        } elseif (is_string($photo)) {
            $user->forceFill([
                'profile_photo_path' => $photo,
            ])->save();
        } elseif ($photo === null && filled($user->profile_photo_path)) {
            $user->deleteProfilePhoto();
        }

        if ($input['email'] !== $user->email &&
            $this->userMustVerifyEmail()) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Determine if the user must verify their email address.
     */
    protected function userMustVerifyEmail(): bool
    {
        return in_array(MustVerifyEmail::class, class_implements(User::class));
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
