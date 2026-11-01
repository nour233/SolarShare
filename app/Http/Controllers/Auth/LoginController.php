<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $remember = $request->boolean('remember');

        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages(['email' => 'Adresse e-mail ou mot de passe incorrect.']);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // Password is correct but 2FA is on: ask for the code before logging in.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.two_factor', ['id' => $user->id, 'remember' => $remember, 'expires_at' => now()->addMinutes(5)->timestamp]);

            return redirect()->route('two-factor.challenge');
        }

        return $this->login($request, $user, $remember);
    }

    public function challenge(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, TwoFactorService $twoFactor)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'La vérification a expiré. Connectez-vous à nouveau.']);
        }

        $data = $request->validate(['code' => ['nullable', 'string', 'max:20'], 'recovery_code' => ['nullable', 'string', 'max:20']]);
        $valid = filled($data['recovery_code'] ?? null)
            ? $twoFactor->useRecoveryCode($user, $data['recovery_code'])
            : $twoFactor->verify($user->two_factor_secret, (string) ($data['code'] ?? ''));

        if (! $valid) {
            throw ValidationException::withMessages(['code' => filled($data['recovery_code'] ?? null) ? 'Code de récupération invalide ou déjà utilisé.' : 'Code incorrect. Vérifiez l’heure de votre téléphone et réessayez.']);
        }

        $remember = $request->session()->pull('login.two_factor')['remember'] ?? false;

        return $this->login($request, $user, $remember);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function login(Request $request, User $user, bool $remember)
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $user->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('home');
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('login.two_factor');
        if (! $pending || $pending['expires_at'] < now()->timestamp) {
            $request->session()->forget('login.two_factor');

            return null;
        }

        return User::find($pending['id']);
    }
}
