<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceEnrollmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'descriptor' => ['required', 'array', 'size:128'],
            'descriptor.*' => ['required', 'numeric', 'between:-1,1'],
        ]);

        $request->user()->forceFill(['face_descriptor' => $data['descriptor']])->save();

        return response()->json(['message' => 'Connexion par visage activée.']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $request->user()->forceFill(['face_descriptor' => null])->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Connexion par visage désactivée.']);
        }

        return redirect()->route('profile.show')->with('status', 'Connexion par visage désactivée.');
    }
}
