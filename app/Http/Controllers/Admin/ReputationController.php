<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReputationController extends Controller
{
    public function index(): View
    {
        $reviews = Review::with('user')->latest()->get();
        $ratedReviews = $reviews->whereNotNull('rating');
        $averageRating = $ratedReviews->avg('rating');

        $services = collect(Review::SERVICES)->map(function (string $label, string $service) use ($reviews) {
            $serviceReviews = $reviews->where('service', $service);

            return [
                'service' => $service,
                'label' => $label,
                'count' => $serviceReviews->count(),
                'average' => $serviceReviews->avg('rating'),
                'positive' => $serviceReviews->where('rating', '>=', 4)->count(),
                'negative' => $serviceReviews->where('rating', '<=', 2)->count(),
            ];
        })->values();

        $clients = $reviews->groupBy('user_id')->map(function (Collection $clientReviews) {
            $negative = $clientReviews->where('rating', '<=', 2)->count();

            return [
                'user_id' => $clientReviews->first()->user_id,
                'name' => $clientReviews->first()->user?->name ?? 'Client supprimé',
                'count' => $clientReviews->count(),
                'average' => $clientReviews->avg('rating'),
                'negative' => $negative,
                'negativeRate' => $clientReviews->count() > 0 ? ($negative / $clientReviews->count()) * 100 : 0,
            ];
        })->sortByDesc('count')->values();

        return view('back.reputation.index', [
            'totalReviews' => $reviews->count(),
            'clientCount' => $clients->count(),
            'averageRating' => $averageRating,
            'positiveCount' => $ratedReviews->where('rating', '>=', 4)->count(),
            'negativeCount' => $ratedReviews->where('rating', '<=', 2)->count(),
            'companyLabel' => $this->reputationLabel($averageRating),
            'services' => $services,
            'clients' => $clients,
            'recentReviews' => $reviews->take(8),
        ]);
    }

    public function clients(): View
    {
        $clients = User::whereHas('reviews')
            ->withCount('reviews')
            ->with(['reviews' => fn ($query) => $query->select('id', 'user_id', 'rating')])
            ->get()
            ->map(function (User $client) {
                $client->reviews_average = $client->reviews->avg('rating');
                $client->negative_reviews = $client->reviews->where('rating', '<=', 2)->count();

                return $client;
            })
            ->sortByDesc('reviews_count')
            ->values();

        return view('back.reputation.clients', compact('clients'));
    }

    public function client(User $user): View
    {
        $reviews = $user->reviews()->latest()->paginate(15);

        abort_if($reviews->total() === 0, 404);

        return view('back.reputation.client', [
            'client' => $user,
            'reviews' => $reviews,
            'averageRating' => $user->reviews()->avg('rating'),
        ]);
    }

    public function destroyReview(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('status', 'Avis supprimé de la réputation.');
    }

    private function reputationLabel(?float $average): string
    {
        return match (true) {
            $average === null => 'Pas encore évaluée',
            $average >= 4 => 'Bonne réputation',
            $average >= 3 => 'Réputation moyenne',
            default => 'Réputation à améliorer',
        };
    }
}
