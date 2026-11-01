<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FaceLoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'descriptor' => ['required', 'array', 'size:128'],
            'descriptor.*' => ['required', 'numeric', 'between:-1,1'],
        ]);
        $user = User::whereNotNull('face_descriptor')->get()->first(
            fn (User $candidate): bool => $this->distance($candidate->face_descriptor, $data['descriptor']) <= 0.55
        );

        if (! $user) {
            throw ValidationException::withMessages(['descriptor' => 'Visage non reconnu. Utilisez votre mot de passe pour vous connecter.']);
        }

        $request->session()->put('face_login.user_id', $user->id);
        $request->session()->put('face_login.expires_at', now()->addMinutes(5)->timestamp);

        return response()->json(['redirect' => route('login.face.password')]);
    }

    public function passwordForm(Request $request)
    {
        abort_unless($this->pendingUser($request), 403);

        return view('auth.face-password');
    }

    public function verifyPassword(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'La vérification du visage a expiré.']);
        }

        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }

        $request->session()->forget('face_login');
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.two_factor', [
                'id' => $user->id,
                'remember' => false,
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'home');
    }

    private function distance(array $first, array $second): float
    {
        if (count($first) !== 128 || count($second) !== 128) {
            return 1.0;
        }

        return sqrt(array_sum(array_map(
            static fn (float|int $value, int $index): float => ($value - (float) $second[$index]) ** 2,
            $first,
            array_keys($first)
        )));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('face_login.user_id');
        $expiresAt = $request->session()->get('face_login.expires_at', 0);

        if (! $id || $expiresAt < now()->timestamp) {
            $request->session()->forget('face_login');
            return null;
        }

        return User::find($id);
    }
}
