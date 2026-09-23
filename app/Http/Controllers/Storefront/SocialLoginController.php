<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\UserStatus;
use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AffiliateService;
use App\Settings\AuthSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Google sign-in.
 *
 * The OAuth client is configured from the admin panel rather than the environment, so the
 * credentials are pushed into Socialite's config at request time.
 */
final class SocialLoginController extends Controller
{
    public function __construct(
        private readonly AuthSettings $settings,
        private readonly AffiliateService $affiliate,
    ) {}

    public function redirect(): SymfonyRedirect
    {
        $this->assertEnabled();
        $this->configureDriver();

        // Socialite returns Symfony's redirect, not Laravel's subclass of it.
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->assertEnabled();
        $this->configureDriver();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('socialite.google.failed', ['message' => $e->getMessage()]);

            return redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => 'Google did not share an email address.']);
        }

        // An existing account with that address is linked rather than duplicated: the
        // customer proved control of the mailbox either way.
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user === null) {
            if (! $this->settings->registration_enabled) {
                return redirect()->route('login')->withErrors(['email' => 'Registration is currently closed.']);
            }

            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => null,
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'avatar_url' => null,
            ]);

            $this->affiliate->attachReferrer($user, $request->cookie('ref'));
            event(new UserRegistered($user));
        }

        if ($user->status !== UserStatus::Active) {
            return redirect()->route('login')->withErrors(['email' => 'This account has been suspended.']);
        }

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        auth()->login($user, true);
        $request->session()->regenerate();

        return redirect()->intended('/customer');
    }

    private function assertEnabled(): void
    {
        abort_unless(
            $this->settings->google_login_enabled
            && $this->settings->google_client_id !== ''
            && $this->settings->google_client_secret !== '',
            404,
        );
    }

    private function configureDriver(): void
    {
        Config::set('services.google', [
            'client_id' => $this->settings->google_client_id,
            'client_secret' => $this->settings->google_client_secret,
            'redirect' => route('auth.google.callback'),
        ]);
    }
}
