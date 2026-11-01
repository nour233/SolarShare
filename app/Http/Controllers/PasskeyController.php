<?php

namespace App\Http\Controllers;

use App\Services\PasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PasskeyController extends Controller
{
    public function registrationOptions(Request $request, PasskeyService $passkeys): JsonResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        return response()->json($passkeys->beginRegistration($request->user()));
    }

    public function register(Request $request, PasskeyService $passkeys): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'response.clientDataJSON' => ['required', 'string'],
            'response.attestationObject' => ['required', 'string'],
            'type' => ['required', 'string', 'in:public-key'],
        ]);

        try {
            $passkey = $passkeys->completeRegistration($request->user(), $data);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Impossible d’enregistrer cet appareil.'], 422);
        }

        return response()->json(['message' => 'Passkey enregistrée.', 'id' => $passkey->id]);
    }

    public function authenticationOptions(Request $request, PasskeyService $passkeys): JsonResponse
    {
        $data = $request->validate(['email' => ['nullable', 'email']]);
        $user = filled($data['email'] ?? null)
            ? \App\Models\User::where('email', $data['email'])->first()
            : null;

        return response()->json($passkeys->beginAuthentication($user));
    }

    public function authenticate(Request $request, PasskeyService $passkeys): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'response.clientDataJSON' => ['required', 'string'],
            'response.authenticatorData' => ['required', 'string'],
            'response.signature' => ['required', 'string'],
            'type' => ['required', 'string', 'in:public-key'],
        ]);

        try {
            $user = $passkeys->completeAuthentication($data);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'La vérification de l’appareil a échoué.'], 422);
        }

        \Illuminate\Support\Facades\Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json(['redirect' => $user->isAdmin() ? route('admin.dashboard') : route('home')]);
    }

    public function destroy(Request $request, \App\Models\Passkey $passkey)
    {
        abort_unless($passkey->user_id === $request->user()->id, 403);
        $passkey->delete();

        return back()->with('status', 'Appareil de confiance supprimé.');
    }
}
