<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TotpService $totp)
    {
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->user()?->two_factor_enabled_at) {
            return redirect()->route('taxi-control.home');
        }

        if ($request->session()->get('2fa_passed') === true) {
            return redirect()->route('taxi-control.home');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        if (! $user?->two_factor_secret || ! $this->totp->verify($user->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => 'Der Sicherheitscode ist ungültig oder abgelaufen.']);
        }

        $request->session()->put('2fa_passed', true);
        $request->session()->regenerate();

        return redirect()->intended(route('taxi-control.home'));
    }

    public function showSetup(Request $request): View
    {
        $secret = $request->session()->get('2fa_setup_secret');
        if (! $secret) {
            $secret = $this->totp->generateSecret();
            $request->session()->put('2fa_setup_secret', $secret);
        }

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'provisioningUri' => $this->totp->provisioningUri($secret, $request->user()->email),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $secret = (string) $request->session()->get('2fa_setup_secret');

        if (! $secret || ! $this->totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'Der Sicherheitscode ist ungültig.']);
        }

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ])->save();

        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('2fa_passed', true);

        return redirect()->route('taxi-control.account.2fa')->with('status', 'Zwei-Faktor-Authentifizierung wurde aktiviert.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['password'], $user->password) || ! $user->two_factor_secret || ! $this->totp->verify($user->two_factor_secret, $data['code'])) {
            return back()->withErrors(['password' => 'Passwort oder Sicherheitscode ist nicht korrekt.']);
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_enabled_at' => null])->save();
        $request->session()->put('2fa_passed', true);

        return redirect()->route('taxi-control.account.2fa')->with('status', 'Zwei-Faktor-Authentifizierung wurde deaktiviert.');
    }
}
