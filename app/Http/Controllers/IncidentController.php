<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Incident;
use App\Models\IncidentMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    /** "Mes signalements": only the incidents the user reported. */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('incidents.index', [
            'incidents' => $user->incidents()->with('equipment')
                ->withCount(['messages as unread_count' => fn ($query) => $query->whereNull('read_at')->where('user_id', '!=', $user->id)])
                ->latest()->paginate(10),
        ]);
    }

    public function create(Equipment $equipment)
    {
        return view('incidents.create', ['equipment' => $equipment->load('category')]);
    }

    public function store(Request $request, Equipment $equipment)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'severity' => ['required', Rule::in(array_keys(Incident::SEVERITIES))],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], self::messages());

        $incident = Incident::create([
            'equipment_id' => $equipment->id,
            'user_id' => $request->user()->id,
            'description' => $data['description'],
            'severity' => $data['severity'],
            'photos' => self::storePhotos($request),
        ]);

        return redirect()->route('incidents.show', $incident)->with('status', 'Merci ! Votre signalement a bien été envoyé.');
    }

    public function show(Request $request, Incident $incident)
    {
        Gate::authorize('view', $incident);
        $incident->markReadFor($request->user());

        return view('incidents.show', [
            'incident' => $incident->load('equipment.category'),
            'messages' => $incident->messages()->with('user')->get(),
        ]);
    }

    /** Badge counter for the navbar and the admin sidebar. */
    public function unreadCount(Request $request)
    {
        return response()->json(['count' => IncidentMessage::unreadCountFor($request->user())]);
    }

    /** French validation messages, shared with the admin form. */
    public static function messages(): array
    {
        return [
            'description.required' => 'Décrivez le problème.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'description.max' => 'La description ne peut pas dépasser 2 000 caractères.',
            'severity.required' => 'Choisissez une gravité.',
            'severity.in' => 'Choisissez une gravité valide.',
            'photos.max' => 'Vous pouvez joindre 4 photos au maximum.',
            'photos.*.image' => 'Chaque photo doit être une image.',
            'photos.*.mimes' => 'Les photos doivent être au format JPG, PNG ou WebP.',
            'photos.*.max' => 'Chaque photo doit faire 5 Mo au maximum.',
            'equipment_id.required' => 'Choisissez un équipement.',
            'equipment_id.exists' => 'Cet équipement n’existe pas.',
            'user_id.required' => 'Choisissez l’utilisateur qui signale l’incident.',
            'user_id.exists' => 'Cet utilisateur n’existe pas.',
            'status.required' => 'Choisissez un statut.',
            'status.in' => 'Choisissez un statut valide.',
        ];
    }

    /** Photos are stored like the equipment photos: "storage/incidents/...". */
    public static function storePhotos(Request $request): array
    {
        return array_map(fn ($file) => 'storage/'.$file->store('incidents', 'public'), $request->file('photos', []));
    }

    public static function deletePhoto(string $photo): void
    {
        if (preg_match('#^storage/incidents/[a-zA-Z0-9._-]+$#', $photo)) {
            Storage::disk('public')->delete(substr($photo, 8));
        }
    }
}
