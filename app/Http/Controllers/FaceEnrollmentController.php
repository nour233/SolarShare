<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class FaceEnrollmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'descriptor' => ['required', 'array', 'size:128'],
            'descriptor.*' => ['required', 'numeric', 'between:-1,1'],
        ]);

        DB::table('users')
            ->where('id', $request->user()->getKey())
            ->update([
                'face_descriptor' => Crypt::encrypt(json_encode($data['descriptor'], JSON_THROW_ON_ERROR), false),
            ]);

        return response()->json(['message' => 'Connexion par visage activée.']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        DB::table('users')
            ->where('id', $request->user()->getKey())
            ->update(['face_descriptor' => null]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Connexion par visage désactivée.']);
        }

        return redirect()->route('profile.show')->with('status', 'Connexion par visage désactivée.');
    }
}
