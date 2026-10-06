<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $equipmentId = $request->integer('equipment') ?: null;

        return view('back.maintenances.index', [
            'maintenances' => Maintenance::with('equipment')
                ->when($equipmentId, fn ($query, $id) => $query->where('equipment_id', $id))
                ->orderByDesc('date')->orderByDesc('id')
                ->paginate(15)->withQueryString(),
            'equipments' => Equipment::orderBy('title')->get(['id', 'title']),
            'equipmentId' => $equipmentId,
        ]);
    }

    public function create(Request $request)
    {
        return $this->form(new Maintenance(['equipment_id' => $request->integer('equipment') ?: null, 'date' => today(), 'cost' => 0]));
    }

    public function store(Request $request)
    {
        Maintenance::create($this->data($request));

        return redirect()->route('admin.maintenances.index')->with('status', 'Maintenance enregistrée.');
    }

    public function edit(Maintenance $maintenance)
    {
        return $this->form($maintenance);
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $maintenance->update($this->data($request));

        return redirect()->route('admin.maintenances.index')->with('status', 'Maintenance mise à jour.');
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->delete();

        return back()->with('status', 'Maintenance supprimée.');
    }

    private function form(Maintenance $maintenance)
    {
        return view('back.maintenances.form', [
            'maintenance' => $maintenance,
            'equipments' => Equipment::orderBy('title')->get(['id', 'title']),
        ]);
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id'],
            'type' => ['required', Rule::in(array_keys(Maintenance::TYPES))],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'equipment_id.required' => 'Choisissez un équipement.',
            'equipment_id.exists' => 'Cet équipement n’existe pas.',
            'type.required' => 'Choisissez un type d’intervention.',
            'type.in' => 'Choisissez un type d’intervention valide.',
            'date.required' => 'Indiquez la date de l’intervention.',
            'date.date' => 'La date n’est pas valide.',
            'date.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'cost.required' => 'Indiquez le coût (0 si gratuit).',
            'cost.numeric' => 'Le coût doit être un nombre.',
            'cost.min' => 'Le coût ne peut pas être négatif.',
            'cost.max' => 'Le coût est trop élevé.',
            'notes.max' => 'Les notes ne peuvent pas dépasser 2 000 caractères.',
        ]);

        return $data + ['notes' => null]; // an empty notes field clears the old notes
    }
}
