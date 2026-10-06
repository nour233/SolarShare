<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $data['password'] = Hash::make($data['password']);
        $this->sendCode($request, $data);

        return redirect()->route('register.verify');
    }

    public function verification(Request $request)
    {
        if (! $request->session()->has('registration')) {
            return redirect()->route('register');
        }

        return view('auth.verify-code', ['email' => $request->session()->get('registration.email')]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'regex:/^[0-9]{6}$/']]);
        $pending = $request->session()->get('registration');

        if (! $pending) {
            return redirect()->route('register');
        }

        if ($pending['expires_at'] < now()->timestamp || $pending['attempts'] >= 5) {
            throw ValidationException::withMessages(['code' => 'Code expiré ou trop de tentatives. Demandez un nouveau code.']);
        }

        $request->session()->put('registration.attempts', $pending['attempts'] + 1);
        if (! Hash::check($request->input('code'), $pending['code_hash'])) {
            throw ValidationException::withMessages(['code' => 'Le code est incorrect.']);
        }

        if (User::where('email', $pending['email'])->exists()) {
            $request->session()->forget('registration');
            return redirect()->route('register')->withErrors(['email' => 'Cette adresse possède déjà un compte.']);
        }

        $user = User::create(collect($pending)->only(['name', 'email', 'password'])->all());
        $user->forceFill(['email_verified_at' => now()])->save();
        $request->session()->forget('registration');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function resend(Request $request)
    {
        $pending = $request->session()->get('registration');
        if (! $pending) {
            return redirect()->route('register');
        }
        if ($pending['sent_at'] > now()->timestamp - 60) {
            throw ValidationException::withMessages(['code' => 'Patientez une minute avant de renvoyer le code.']);
        }
        $this->sendCode($request, collect($pending)->only(['name', 'email', 'password'])->all());

        return redirect()->route('register.verify')->with('status', 'Un nouveau code a été envoyé.');
    }

    private function sendCode(Request $request, array $data): void
    {
        $code = (string) random_int(100000, 999999);
        try {
            Notification::route('mail', $data['email'])->notify(new RegistrationCode($code));
        } catch (\Throwable $exception) {
            // Do not log SMTP credentials or the verification code.
            \Illuminate\Support\Facades\Log::warning('Registration email delivery failed.', ['type' => get_class($exception)]);
            throw ValidationException::withMessages(['email' => 'Impossible d’envoyer le code. Réessayez dans quelques instants.']);
        }
        $request->session()->put('registration', array_merge($data, [
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
        ]));
    }
}
