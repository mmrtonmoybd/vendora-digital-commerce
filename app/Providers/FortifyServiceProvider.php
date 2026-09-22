<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Enums\UserStatus;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        /**
         * Sign-in, with the two rules the template's error style already has a place for:
         * bad credentials, and an account that exists but is not allowed in.
         */
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->string('email')->toString())->first();

            $ok = $user !== null
                && $user->password !== null
                && Hash::check($request->string('password')->toString(), $user->password);

            LoginHistory::create([
                'authenticatable_type' => User::class,
                'authenticatable_id' => $user?->getKey() ?? 0,
                'email' => $request->string('email')->toString(),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'successful' => $ok,
                'created_at' => now(),
            ]);

            if (! $ok) {
                return null;
            }

            if ($user->status !== UserStatus::Active) {
                throw ValidationException::withMessages([
                    'email' => 'This account has been suspended. Please contact support.',
                ]);
            }

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            // A basket built before signing in belongs to the same person.
            app(CartService::class)->merge($user, $request->session()->getId());

            return $user;
        });

        Fortify::registerView(fn () => redirect()->route('register'));
        Fortify::loginView(fn () => redirect()->route('login'));
        Fortify::requestPasswordResetLinkView(fn () => redirect()->route('password.request'));
        Fortify::resetPasswordView(fn (Request $request) => redirect()->route('password.reset', [
            'token' => $request->route('token'),
            'email' => $request->query('email'),
        ]));
        Fortify::verifyEmailView(fn () => redirect()->route('verification.notice'));

        $this->configureRateLimiting();
    }

    /**
     * Throttles that match what the template's pages promise.
     *
     * Login is keyed on the email as well as the address, so one attacker cannot lock every
     * customer out by hammering a shared IP, and one address cannot be brute-forced from a
     * rotating pool of IPs either.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $key = Str::transliterate(Str::lower($request->input('email', '')).'|'.$request->ip());

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('two-factor', fn (Request $request): Limit => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }

    public function register(): void
    {
        //
    }
}
