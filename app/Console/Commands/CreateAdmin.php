<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use App\Rules\PhoneNumber;
use App\Services\AuditService;
use App\Support\Phone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates the platform owner's admin account from the terminal, so the
 * password is typed by the owner and never stored anywhere else. If the
 * email already belongs to an account (e.g. signed up via Google), that
 * account is promoted instead.
 */
#[Signature('app:create-admin {email? : Email address of the admin}')]
#[Description('Create an administrator account, or grant admin to an existing account')]
class CreateAdmin extends Command
{
    public function handle(AuditService $audit): int
    {
        $email = Str::lower($this->argument('email') ?? text(
            label: 'Admin email',
            required: true,
            validate: fn (string $value) => $this->check('email', $value, ['email:rfc', 'max:255']),
        ));

        if ($existing = User::query()->where('email', $email)->first()) {
            if ($existing->hasRole(RoleName::Admin->value)) {
                $this->components->info("{$email} is already an admin.");

                return self::SUCCESS;
            }

            if (! confirm("An account for {$email} already exists. Make it an admin?")) {
                return self::FAILURE;
            }

            $existing->assignRole(RoleName::Admin->value);
            $audit->log('admin.role_granted', $existing, meta: ['role' => RoleName::Admin->value, 'via' => 'console']);
            $this->components->info("{$email} is now an admin.");

            return self::SUCCESS;
        }

        $name = text(label: 'Full name', required: true, validate: fn (string $value) => $this->check('name', $value, ['min:3', 'max:255']));
        $phone = Phone::normalize(text(
            label: 'Phone number (international format)',
            placeholder: '+254712345678',
            required: true,
            validate: fn (string $value) => $this->check('phone', Phone::normalize($value), [new PhoneNumber, Rule::unique(User::class)]),
        ));
        $country = strtoupper(text(label: 'Country code (2 letters)', default: 'KE', required: true, validate: fn (string $value) => $this->check('country', $value, ['size:2'])));
        $dateOfBirth = text(label: 'Date of birth (YYYY-MM-DD)', required: true, validate: fn (string $value) => $this->check('date_of_birth', $value, ['date_format:Y-m-d', 'before:today']));
        $password = password(label: 'Password', required: true, validate: fn (string $value) => $this->check('password', $value, [Password::defaults()]));

        if (password(label: 'Confirm password', required: true) !== $password) {
            $this->components->error('Passwords do not match.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'country' => $country,
            'date_of_birth' => $dateOfBirth,
            'password' => $password,
        ]);

        // Created by the owner at the console, so the email is taken as verified.
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(RoleName::Admin->value);
        $audit->log('admin.created', $user, meta: ['via' => 'console']);

        $this->components->info("Admin account created for {$email}.");
        $this->components->warn('On first sign-in you will be asked to turn on two-factor authentication (required for staff).');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function check(string $field, string $value, array $rules): ?string
    {
        $validator = Validator::make([$field => $value], [$field => ['required', ...$rules]]);

        return $validator->fails() ? $validator->errors()->first($field) : null;
    }
}
