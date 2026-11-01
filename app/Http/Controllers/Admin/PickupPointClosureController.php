<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use App\Models\PickupPointClosure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PickupPointClosureController extends Controller
{
    public function store(Request $request, PickupPoint $pickupPoint): RedirectResponse
    {
        $validated = $request->validate([
            'closure_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $this->ensureDateIsAvailable($pickupPoint, $validated['closure_date']);

        $pickupPoint->closures()->create($validated);

        return redirect()
            ->route('admin.pickup-points.show', $pickupPoint)
            ->with('success', 'La fermeture exceptionnelle a été ajoutée.');
    }

    public function update(
        Request $request,
        PickupPoint $pickupPoint,
        PickupPointClosure $closure
    ): RedirectResponse {
        abort_unless($closure->pickup_point_id === $pickupPoint->id, 404);

        $validated = $request->validate([
            'closure_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $this->ensureDateIsAvailable($pickupPoint, $validated['closure_date'], $closure);

        $closure->update($validated);

        return redirect()
            ->route('admin.pickup-points.show', $pickupPoint)
            ->with('success', 'La fermeture exceptionnelle a été modifiée.');
    }

    public function destroy(
        PickupPoint $pickupPoint,
        PickupPointClosure $closure
    ): RedirectResponse {
        abort_unless($closure->pickup_point_id === $pickupPoint->id, 404);
        $closure->delete();

        return redirect()
            ->route('admin.pickup-points.show', $pickupPoint)
            ->with('success', 'La fermeture exceptionnelle a été supprimée.');
    }

    private function ensureDateIsAvailable(
        PickupPoint $pickupPoint,
        string $date,
        ?PickupPointClosure $ignore = null
    ): void {
        $exists = $pickupPoint->closures()
            ->whereDate('closure_date', $date)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'closure_date' => 'Une fermeture est déjà enregistrée pour cette date.',
            ]);
        }
    }

}
