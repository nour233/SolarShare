<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request, TwoFactorService $twoFactor)
    {
        $user = $request->user();

        return view('profile.show', [
            'user' => $user,
            'faceDescriptorAvailable' => $user->usableFaceDescriptor() !== null,
            'logisticsPreference' => $user->isAdmin()
                ? null
                : $user->logisticsPreference()->with('preferredPickupPoint')->first(),
            'pickupPoints' => $user->isAdmin()
                ? collect()
                : PickupPoint::query()->where('status', 'active')->orderBy('city')->orderBy('name')->get(),
            // While 2FA is being set up (secret saved, not confirmed yet), show the QR code.
            'qrCode' => $user->two_factor_secret && ! $user->hasTwoFactorEnabled() ? $twoFactor->qrCodeSvg($user, $user->two_factor_secret) : null,
        ]);
    }

    public function updateLogisticsPreferences(Request $request)
    {
        $data = $request->validateWithBag('logisticsPreferences', [
            'preferred_pickup_point_id' => [
                'nullable',
                'integer',
                'exists:pickup_points,id,status,active',
            ],
            'preferred_start_time' => [
                'nullable',
                'required_with:preferred_end_time',
                'date_format:H:i',
            ],
            'preferred_end_time' => [
                'nullable',
                'required_with:preferred_start_time',
                'date_format:H:i',
                'after:preferred_start_time',
            ],
            'preferred_city' => ['nullable', 'string', 'max:100'],
        ], [
            'preferred_pickup_point_id.exists' => 'Sélectionnez un point de retrait actif.',
            'preferred_start_time.required_with' => 'Indiquez les deux heures de votre plage préférée.',
            'preferred_end_time.required_with' => 'Indiquez les deux heures de votre plage préférée.',
            'preferred_end_time.after' => 'L’heure de fin doit être après l’heure de début.',
        ]);

        $request->user()->logisticsPreference()->updateOrCreate([], $data);

        return back()->with('status', 'Vos préférences de retrait ont été enregistrées.');
    }

    public function resetLogisticsPreferences(Request $request)
    {
        $request->user()->logisticsPreference()->delete();

        return back()->with('status', 'Vos préférences de retrait ont été réinitialisées.');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            // Changing the e-mail (the login) requires the current password.
            'current_password' => [Rule::requiredIf($request->input('email') !== $user->email), 'nullable', 'current_password'],
        ], self::messages());

        $oldAvatar = $user->avatar;
        if ($request->hasFile('avatar')) {
            $user->avatar = 'storage/'.$request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar')) {
            $user->avatar = null;
        }
        $user->fill(['name' => $data['name'], 'email' => $data['email']])->save();

        if ($oldAvatar && $oldAvatar !== $user->avatar) {
            self::deleteAvatar($oldAvatar);
        }

        return back()->with('status', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], self::messages());

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Mot de passe modifié.');
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('deletion', ['delete_password' => ['required', 'current_password']], self::messages());
        $user = $request->user();

        if ($user->isAdmin()) {
            return back()->withErrors(['delete_password' => 'Un administrateur ne peut pas supprimer son propre compte.'], 'deletion');
        }
        if ($user->rentals()->exists() || $user->equipments()->exists()) {
            return back()->withErrors(['delete_password' => 'Votre compte est lié à des locations (factures, contrats) : contactez l’équipe SolarShare pour le supprimer.'], 'deletion');
        }

        Auth::logout();
        $user->delete();
        if ($user->avatar) {
            self::deleteAvatar($user->avatar);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Votre compte a été supprimé.');
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'Indiquez votre nom.',
            'email.required' => 'Indiquez votre adresse e-mail.',
            'email.email' => 'L’adresse e-mail n’est pas valide.',
            'email.unique' => 'Cette adresse est déjà utilisée par un autre compte.',
            'avatar.image' => 'La photo doit être une image.',
            'avatar.mimes' => 'La photo doit être au format JPG, PNG ou WebP.',
            'avatar.max' => 'La photo doit faire 2 Mo au maximum.',
            'current_password.required' => 'Saisissez votre mot de passe actuel.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required' => 'Saisissez le nouveau mot de passe.',
            'password.confirmed' => 'La confirmation ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l’actuel.',
            'delete_password.required' => 'Saisissez votre mot de passe pour confirmer.',
            'delete_password.current_password' => 'Le mot de passe est incorrect.',
            'code.required' => 'Saisissez le code à 6 chiffres.',
        ];
    }

    private static function deleteAvatar(string $avatar): void
    {
        if (preg_match('#^storage/avatars/[a-zA-Z0-9._-]+$#', $avatar)) {
            Storage::disk('public')->delete(substr($avatar, 8));
        }
    }
}
