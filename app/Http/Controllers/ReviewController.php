<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'rating_filter' => ['nullable', 'integer', 'between:1,5'],
            'user_search' => ['nullable', 'string', 'max:255'],
        ]);
        $rating = $data['rating_filter'] ?? null;
        $userSearch = trim($data['user_search'] ?? '');

        return view('reviews.index', [
            'reviews' => Review::with('user')
                ->when($rating, fn ($query) => $query->where('rating', $rating))
                ->when($userSearch, fn ($query) => $query->whereHas('user', function ($userQuery) use ($userSearch) {
                    $escapedSearch = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $userSearch);
                    $userQuery->whereRaw("name LIKE ? ESCAPE '!'", ['%'.$escapedSearch.'%']);
                }))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'rating' => $rating,
            'userSearch' => $userSearch,
        ]);
    }

    public function create()
    {
        return view('reviews.create');
    }

    public function store(Request $request, SentimentAnalysisService $sentiment)
    {
        $data = $this->validated($request);
        $analysis = $sentiment->analyze($data['title'].' '.$data['comment']);
        $request->user()->reviews()->create($data + [
            'sentiment_label' => $analysis['label'],
            'sentiment_score' => $analysis['score'],
            'sentiment_source' => $analysis['source'],
        ]);

        return redirect()->route('reviews.index')->with('status', 'Merci pour votre avis !');
    }

    public function edit(Request $request, Review $review)
    {
        $this->authorizeOwner($request, $review);

        return view('reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review, SentimentAnalysisService $sentiment)
    {
        $this->authorizeOwner($request, $review);
        $data = $this->validated($request);
        $analysis = $sentiment->analyze($data['title'].' '.$data['comment']);
        $review->update($data + [
            'sentiment_label' => $analysis['label'],
            'sentiment_score' => $analysis['score'],
            'sentiment_source' => $analysis['source'],
        ]);

        return redirect()->route('reviews.index')->with('status', 'Votre avis a été modifié.');
    }

    public function destroy(Request $request, Review $review)
    {
        abort_unless($review->canBeDeletedBy($request->user()), 403);
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
            'service' => ['required', Rule::in(array_keys(Review::SERVICES))],
            'title' => ['required', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
    }
}
