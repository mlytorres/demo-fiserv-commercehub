<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Single shared team login for the demo — see config/demo_auth.php.
 * Not a real user system: one username/password pair from .env, a boolean
 * session flag, no registration, no per-person accounts.
 */
class DemoAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('fiserv.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $expectedUsername = (string) config('demo_auth.username');
        $expectedPassword = (string) config('demo_auth.password');

        $credentialsConfigured = $expectedUsername !== '' && $expectedPassword !== '';

        // hash_equals for constant-time comparison — these are short-lived
        // shared-team credentials, not per-user hashed passwords, so there's
        // nothing to Hash::check() against; this just avoids a timing side
        // channel on the string comparison.
        $matches = $credentialsConfigured
            && hash_equals($expectedUsername, $validated['username'])
            && hash_equals($expectedPassword, $validated['password']);

        if (! $matches) {
            return back()
                ->withErrors(['username' => $credentialsConfigured
                    ? 'Incorrect username or password.'
                    : 'DEMO_AUTH_USERNAME / DEMO_AUTH_PASSWORD are not set in .env yet.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('demo_authenticated', true);

        return redirect()->intended(route('fiserv.demo.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('demo_authenticated');
        $request->session()->regenerate();

        return redirect()->route('fiserv.demo.login');
    }
}
