<?php

namespace App\Actions\Fortify;

use App\Enums\RoleName;
use App\Models\User;
use App\Rules\AllowedCountry;
use App\Rules\PhoneNumber;
use App\Services\Wallet\WalletService;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly WalletService $wallets) {}

    /**
     * Validate and create a newly registered customer.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        if (! config('tradevault.registration.enabled')) {
            throw ValidationException::withMessages(['email' => __('Registration is currently closed.')]);
        }

        if (isset($input['phone']) && is_string($input['phone'])) {
            $input['phone'] = Phone::normalize($input['phone']);
        }

        $minimumAge = (int) config('tradevault.registration.minimum_age');

        Validator::make($input, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'string', new PhoneNumber, Rule::unique(User::class)],
            'country' => ['required', 'string', 'size:2', new AllowedCountry],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears($minimumAge)->toDateString(), 'after:1900-01-01'],
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
            'privacy' => ['accepted'],
        ], [
            'date_of_birth.before_or_equal' => __('You must be at least :age years old to register.', ['age' => $minimumAge]),
            'terms.accepted' => __('You must accept the Terms and Conditions and Risk Disclosure.'),
            'privacy.accepted' => __('You must accept the Privacy Policy.'),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'country' => strtoupper($input['country']),
                'date_of_birth' => $input['date_of_birth'],
                'password' => $input['password'],
            ]);

            $user->forceFill([
                'terms_accepted_at' => now(),
                'terms_version' => config('tradevault.legal.terms_version'),
                'privacy_accepted_at' => now(),
            ])->save();

            $user->assignRole(RoleName::User->value);
            $this->wallets->ensureUserWallets($user);

            return $user;
        });
    }
}
