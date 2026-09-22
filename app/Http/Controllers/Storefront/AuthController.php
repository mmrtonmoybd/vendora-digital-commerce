<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Settings\AuthSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Renders the template's auth pages. Fortify owns what happens when they are submitted.
 */
final class AuthController extends Controller
{
    public function __construct(private readonly AuthSettings $settings) {}

    public function showLogin(): View
    {
        return view('pages.auth.login');
    }

    public function showRegister(Request $request): View
    {
        abort_unless($this->settings->registration_enabled, 404);

        return view('pages.auth.register', [
            // Prefilled from the referral cookie the CaptureReferral middleware set.
            'referralCode' => (string) $request->cookie('ref', ''),
        ]);
    }

    public function showForgotPassword(): View
    {
        return view('pages.auth.forgot-password');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function showVerifyEmail(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->intended('/customer');
        }

        return view('pages.auth.verify-email');
    }
}
