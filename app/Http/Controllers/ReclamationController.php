<?php

namespace App\Http\Controllers;

use App\Models\Reclamation;
use App\Models\User;
use App\Notifications\NewReclamationSubmitted;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReclamationController extends Controller
{
    public function index(Request $request)
    {
        return view('reclamations.index', [
            'reclamations' => $request->user()->reclamations()->latest('reclamation_date')->paginate(10),
        ]);
    }

    public function create()
    {
        return view('reclamations.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $reclamation = $request->user()->reclamations()->create($data);

        // Notify admins
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new NewReclamationSubmitted($reclamation));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('NewReclamationSubmitted notification failed: ' . $e->getMessage());
        }

        return redirect()->route('reclamations.index')->with('status', 'Votre réclamation a bien été envoyée.');
    }

    public function show(Request $request, Reclamation $reclamation)
    {
        abort_unless($reclamation->user_id === $request->user()->id, 403);

        return view('reclamations.show', compact('reclamation'));
    }

    private function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Reclamation::TYPES))],
            'review_author_name' => ['required_if:type,avis', 'nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'reclamation_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
