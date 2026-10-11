<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = $request->input('email');
        $key = 'reset-code:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key.':send', 1)) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Patientez une minute avant de renvoyer un code.']);
        }
        RateLimiter::hit($key.':send', 60);
        $user = User::where('email', $email)->first();
        $code = (string) random_int(100000, 999999);
        try {
            if ($user) $user->notify(new \App\Notifications\PasswordResetCode($code));
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Password reset delivery failed.', ['type' => get_class($exception)]);
            RateLimiter::clear($key.':send');
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Impossible d’envoyer le code pour le moment. Réessayez plus tard.']);
        }
        if ($user) Cache::put($key, Hash::make($code), now()->addMinutes(10));
        $request->session()->put('reset_code_email', $email);
        return redirect()->route('password.code')->with('status', 'Si un compte correspond à cette adresse, un code a été envoyé.');
    }

    public function code(Request $request)
    {
        if (! $request->session()->has('reset_code_email')) return redirect()->route('password.request');
        return view('auth.reset-code', ['email' => $request->session()->get('reset_code_email')]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => ['required', 'regex:/^[0-9]{6}$/']]);
        $email = $request->session()->get('reset_code_email');
        if (! $email) return redirect()->route('password.request');
        $key = 'reset-code:'.hash('sha256', $email);
        return Cache::lock($key.':lock', 10)->block(3, function () use ($request, $email, $key) {
            if (RateLimiter::tooManyAttempts($key.':attempts', 5)) {
                return back()->withErrors(['code' => 'Trop de tentatives. Patientez dix minutes avant de réessayer.']);
            }
            RateLimiter::hit($key.':attempts', 600);
            $hash = Cache::get($key);
            if (! $hash || ! Hash::check($request->input('code'), $hash)) {
                return back()->withErrors(['code' => 'Code incorrect ou expiré. Vérifiez le code ou demandez un nouvel envoi.']);
            }
            Cache::forget($key);
            $user = User::where('email', $email)->first();
            if (! $user) return back()->withErrors(['code' => 'Code incorrect ou expiré.']);
            $token = Password::createToken($user);
            $request->session()->forget('reset_code_email');
            return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
        });
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'], 'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Ce lien est invalide ou expiré. Demandez un nouveau lien.']);
        }
        return redirect()->route('login')->with('status', 'Votre mot de passe a été modifié. Connectez-vous avec votre nouveau mot de passe.');
    }
}
