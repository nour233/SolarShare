<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index()
    {
        return view('reviews.index', [
            'reviews' => Review::with('user')->latest()->paginate(12),
        ]);
    }

    public function create()
    {
        return view('reviews.create');
    }

    public function store(Request $request)
    {
        $request->user()->reviews()->create($this->validated($request));

        return redirect()->route('reviews.index')->with('status', 'Merci pour votre avis !');
    }

    public function edit(Request $request, Review $review)
    {
        $this->authorizeOwner($request, $review);

        return view('reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $this->authorizeOwner($request, $review);
        $review->update($this->validated($request));

        return redirect()->route('reviews.index')->with('status', 'Votre avis a été modifié.');
    }

    public function destroy(Request $request, Review $review)
    {
        $this->authorizeOwner($request, $review);
        $review->delete();

        return redirect()->route('reviews.index')->with('status', 'Votre avis a été supprimé.');
    }

    private function authorizeOwner(Request $request, Review $review): void
    {
        abort_unless($review->canBeModifiedBy($request->user()), 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['required', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
    }
}
