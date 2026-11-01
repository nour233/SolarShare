<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use App\Models\Reclamation;
use App\Services\SentimentAnalysisService;
use App\Services\ReputationAssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class ReputationController extends Controller
{
    public function index(): View
    {
        return view('back.reputation.index', $this->reportData());
    }

    public function download()
    {
        $data = $this->reportData();

        return Pdf::loadView('back.reputation.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download('rapport-reputation-solarshare.pdf');
    }

    public function askAssistant(Request $request, ReputationAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        return response()->json($assistant->answer($validated['question']));
    }

    private function reportData(): array
    {
        $reviews = Review::with('user')->latest()->get();
        $reclamations = Reclamation::latest()->get();
        $sentiment = app(SentimentAnalysisService::class);
        $reviews->each(function (Review $review) use ($sentiment): void {
            $analysis = $review->sentiment_label
                ? ['label' => $review->sentiment_label, 'score' => $review->sentiment_score]
                : $sentiment->fallback($review->title.' '.$review->comment);
            $review->setAttribute('sentiment_label', $analysis['label']);
            $review->setAttribute('sentiment_score', $analysis['score']);
        });
        $reclamations->each(function (Reclamation $reclamation) use ($sentiment): void {
            $analysis = $reclamation->sentiment_label
                ? ['label' => $reclamation->sentiment_label, 'score' => $reclamation->sentiment_score]
                : $sentiment->fallback($reclamation->subject.' '.$reclamation->description);
            $reclamation->setAttribute('sentiment_label', $analysis['label']);
            $reclamation->setAttribute('sentiment_score', $analysis['score']);
        });
        $ratedReviews = $reviews->whereNotNull('rating');
        $averageRating = $ratedReviews->avg('rating');
        $sentimentItems = $reviews->concat($reclamations);
        $sentimentCounts = collect(SentimentAnalysisService::LABELS)
            ->mapWithKeys(fn (string $label): array => [$label => $sentimentItems->where('sentiment_label', $label)->count()]);

        $services = collect(Review::SERVICES)->map(function (string $label, string $service) use ($reviews) {
            $serviceReviews = $reviews->where('service', $service);

            return [
                'service' => $service,
                'label' => $label,
                'count' => $serviceReviews->count(),
                'average' => $serviceReviews->avg('rating'),
                'positive' => $serviceReviews->where('rating', '>=', 4)->count(),
                'negative' => $serviceReviews->where('rating', '<=', 2)->count(),
                'sentiment_average' => $serviceReviews->avg('sentiment_score'),
                'sentiment_positive' => $serviceReviews->where('sentiment_label', 'positive')->count(),
                'sentiment_negative' => $serviceReviews->where('sentiment_label', 'negative')->count(),
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

        $rankedClients = $clients->filter(fn (array $client): bool => $client['average'] !== null);

        return [
            'totalReviews' => $reviews->count(),
            'clientCount' => $clients->count(),
            'averageRating' => $averageRating,
            'positiveCount' => $ratedReviews->where('rating', '>=', 4)->count(),
            'negativeCount' => $ratedReviews->where('rating', '<=', 2)->count(),
            'companyLabel' => $this->reputationLabel($averageRating),
            'services' => $services,
            'clients' => $clients,
            'recentReviews' => $reviews->take(8),
            'bestClients' => $rankedClients->sortByDesc('average')->take(5)->values(),
            'worstClients' => $rankedClients->sortBy('average')->take(5)->values(),
            'sentimentCounts' => $sentimentCounts,
            'sentimentAverage' => $sentimentItems->avg('sentiment_score'),
            'reclamationSentimentCounts' => collect(SentimentAnalysisService::LABELS)
                ->mapWithKeys(fn (string $label): array => [$label => $reclamations->where('sentiment_label', $label)->count()]),
        ];
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
