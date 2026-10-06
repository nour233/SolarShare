<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\IncidentController as FrontIncidentController;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), Incident::STATUSES) ? $request->query('status') : null;

        return view('back.incidents.index', [
            'incidents' => Incident::with(['equipment', 'user'])
                // Unread for the admins = messages written by the reporter.
                ->withCount(['messages as unread_count' => fn ($query) => $query->whereNull('read_at')
                    ->whereColumn('incident_messages.user_id', 'incidents.user_id')
                    ->where('incident_messages.user_id', '!=', $request->user()->id)])
                ->when($status, fn ($query, $status) => $query->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Request $request, Incident $incident)
    {
        $incident->markReadFor($request->user());

        return view('back.incidents.show', [
            'incident' => $incident->load(['equipment', 'user']),
            'messages' => $incident->messages()->with('user')->get(),
        ]);
    }

    public function create()
    {
        return $this->form(new Incident(['severity' => 'mineur']));
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['photos'] = FrontIncidentController::storePhotos($request);
        Incident::create($data);

        return redirect()->route('admin.incidents.index')->with('status', 'Incident créé.');
    }

    public function edit(Incident $incident)
    {
        return $this->form($incident);
    }

    public function update(Request $request, Incident $incident)
    {
        $data = $this->data($request);
        $current = $incident->photos ?? [];
        $remove = array_intersect($current, $request->input('remove_photos', []));
        $data['photos'] = array_merge(array_values(array_diff($current, $remove)), FrontIncidentController::storePhotos($request));
        $incident->update($data);
        array_map([FrontIncidentController::class, 'deletePhoto'], $remove);

        return redirect()->route('admin.incidents.show', $incident)->with('status', 'Incident mis à jour.');
    }

    public function updateStatus(Request $request, Incident $incident)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Incident::STATUSES))]], FrontIncidentController::messages());
        $incident->update($data);

        return back()->with('status', 'Statut mis à jour : '.$incident->statusLabel().'.');
    }

    public function destroy(Incident $incident)
    {
        $photos = $incident->photos ?? [];
        $incident->delete();
        array_map([FrontIncidentController::class, 'deletePhoto'], $photos);

        return redirect()->route('admin.incidents.index')->with('status', 'Incident supprimé.');
    }

    private function form(Incident $incident)
    {
        return view('back.incidents.form', [
            'incident' => $incident,
            'equipments' => Equipment::orderBy('title')->get(['id', 'title']),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id'],
            'user_id' => ['required', 'exists:users,id'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'severity' => ['required', Rule::in(array_keys(Incident::SEVERITIES))],
            'status' => ['required', Rule::in(array_keys(Incident::STATUSES))],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
        ], FrontIncidentController::messages());
        unset($data['photos'], $data['remove_photos']);

        return $data;
    }
}
