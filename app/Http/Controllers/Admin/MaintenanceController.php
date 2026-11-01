<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Incident;
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
        $maintenance = new Maintenance(['equipment_id' => $request->integer('equipment') ?: null, 'date' => today(), 'cost' => 0]);
        $incident = Incident::find($request->integer('incident'));

        // Pre-filled from the AI diagnosis of an incident.
        if ($diagnosis = $incident?->ai_diagnosis) {
            $maintenance->fill([
                'equipment_id' => $incident->equipment_id,
                'type' => $diagnosis['action'],
                'cost' => round(($diagnosis['cost_min'] + $diagnosis['cost_max']) / 2, 2),
                'notes' => 'Incident #'.$incident->id.' — '.$diagnosis['cause'],
            ]);
        }

        return $this->form($maintenance, $incident);
    }

    public function store(Request $request)
    {
        $incident = Incident::find($request->integer('incident_id'));
        Maintenance::create($this->data($request) + ['incident_id' => $incident?->id]);

        if ($incident) {
            if ($incident->status === 'ouvert') {
                $incident->update(['status' => 'en_cours']);
            }

            return redirect()->route('admin.incidents.show', $incident)->with('status', 'Maintenance planifiée, incident passé « En cours ».');
        }

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

    private function form(Maintenance $maintenance, ?Incident $incident = null)
    {
        return view('back.maintenances.form', [
            'maintenance' => $maintenance,
            'equipments' => Equipment::orderBy('title')->get(['id', 'title']),
            'incident' => $incident,
        ]);
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id'],
            'type' => ['required', Rule::in(array_keys(Maintenance::TYPES))],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
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
            'duration_days.integer' => 'La durée doit être un nombre entier de jours.',
            'duration_days.min' => 'La durée doit être d’au moins 1 jour.',
            'duration_days.max' => 'La durée ne peut pas dépasser 365 jours.',
            'notes.max' => 'Les notes ne peuvent pas dépasser 2 000 caractères.',
        ]);

        return $data + ['notes' => null, 'duration_days' => null]; // empty fields clear the old values
    }
}
