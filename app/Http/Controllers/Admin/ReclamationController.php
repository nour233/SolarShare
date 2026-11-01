<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use App\Notifications\ReclamationAnswered;
use App\Services\ReclamationResponseDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReclamationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_keys(Reclamation::TYPES))],
            'status' => ['nullable', Rule::in(array_keys(Reclamation::STATUSES))],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'subject', 'client'])],
        ]);
        $sort = $filters['sort'] ?? 'newest';
        $query = Reclamation::with('user')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            }))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        if ($sort === 'client') {
            $query->join('users', 'users.id', '=', 'reclamations.user_id')
                ->select('reclamations.*')
                ->orderBy('users.name');
        } elseif ($sort === 'subject') {
            $query->orderBy('subject');
        } else {
            $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');
        }

        $reclamations = $query->paginate(15)->withQueryString();
        $data = ['reclamations' => $reclamations, 'filters' => $filters + ['sort' => $sort]];

        if ($request->expectsJson()) {
            return response()->json(['html' => view('back.reclamations._results', $data)->render()]);
        }

        return view('back.reclamations.index', $data);
    }

    public function show(Reclamation $reclamation)
    {
        return view('back.reclamations.show', compact('reclamation'));
    }

    public function generateResponseDraft(
        Reclamation $reclamation,
        ReclamationResponseDraftService $draftService
    ): JsonResponse {
        return response()->json($draftService->generate($reclamation));
    }

    public function updateStatus(Request $request, Reclamation $reclamation)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Reclamation::STATUSES))],
            'admin_response' => ['nullable', 'string', 'max:5000'],
        ]);
        $reclamation->update($data);

        // Notify client about reclamation status update or response
        try {
            $reclamation->user?->notify(new ReclamationAnswered($reclamation));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ReclamationAnswered notification failed: ' . $e->getMessage());
        }

        return back()->with('status', 'Statut de la réclamation mis à jour.');
    }

    public function destroy(Reclamation $reclamation)
    {
        $reclamation->delete();

        return redirect()->route('admin.reclamations.index')->with('status', 'Réclamation supprimée.');
    }
}
