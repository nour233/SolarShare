<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReclamationController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), Reclamation::STATUSES)
            ? $request->query('status')
            : null;

        return view('back.reclamations.index', [
            'reclamations' => Reclamation::with('user')->when($status, fn ($query) => $query->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Reclamation $reclamation)
    {
        return view('back.reclamations.show', compact('reclamation'));
    }

    public function updateStatus(Request $request, Reclamation $reclamation)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Reclamation::STATUSES))],
        ]);
        $reclamation->update($data);

        return back()->with('status', 'Statut de la réclamation mis à jour.');
    }

    public function destroy(Reclamation $reclamation)
    {
        $reclamation->delete();

        return redirect()->route('admin.reclamations.index')->with('status', 'Réclamation supprimée.');
    }
}
