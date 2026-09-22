<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\UserStatus;
use App\Events\UserRegistered;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\CartService;
use App\Services\OrderSourceCaptureService;
use App\Settings\AuthSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private readonly AuthSettings $settings,
        private readonly AffiliateService $affiliate,
        private readonly CartService $carts,
        private readonly OrderSourceCaptureService $sources,
    ) {}

    /**
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        if (! $this->settings->registration_enabled) {
            throw ValidationException::withMessages([
                'email' => 'Registration is currently closed.',
            ]);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => $this->passwordRules(),
            'referral_code' => ['nullable', 'string', 'max:12'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please accept the terms to create an account.',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? null,
            'password' => Hash::make($input['password']),
            'status' => UserStatus::Active,
            // Which platform's ad brought them here, so a signup can be attributed even
            // before they buy anything.
            'signup_source_id' => $this->sources->resolveForOrder()?->getKey(),
        ]);

        // The referral field is prefilled from the ?ref= cookie, but the cookie is the
        // fallback: a code typed by hand still counts.
        $this->affiliate->attachReferrer($user, $input['referral_code'] ?? request()->cookie('ref'));

        $this->carts->merge($user, request()->session()->getId());

        UserRegistered::dispatch($user);

        return $user;
    }
}
