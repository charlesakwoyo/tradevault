<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\PhoneNumber;
use App\Services\AuditService;
use App\Support\Phone;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

/**
 * Country and date of birth are identity attributes tied to KYC and cannot be
 * self-edited after registration; support can correct them on request.
 */
class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        if (isset($input['phone']) && is_string($input['phone'])) {
            $input['phone'] = Phone::normalize($input['phone']);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', new PhoneNumber, Rule::unique('users')->ignore($user->id)],
        ])->validateWithBag('updateProfileInformation');

        $emailChanged = $input['email'] !== $user->email;
        $phoneChanged = $input['phone'] !== $user->phone;

        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        if ($phoneChanged) {
            $user->phone_verified_at = null;
        }

        if (! $user->isDirty()) {
            return;
        }

        $this->audit->logChanges($emailChanged ? 'user.email_changed' : 'user.profile_updated', $user);
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }
}
