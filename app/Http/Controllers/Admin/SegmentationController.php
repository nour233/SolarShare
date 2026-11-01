<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RfmSegmentationService;
use Illuminate\Http\Request;

class SegmentationController extends Controller
{
    /**
     * Display the RFM segmentation dashboard.
     */
    public function index()
    {
        $users = User::where('role', '!=', 'admin')
            ->orderByRaw("FIELD(rfm_segment, 'vip', 'regular', 'new', 'at_risk', 'lost')")
            ->get();

        // Build distribution
        $distribution = [
            'vip' => $users->where('rfm_segment', 'vip')->count(),
            'regular' => $users->where('rfm_segment', 'regular')->count(),
            'at_risk' => $users->where('rfm_segment', 'at_risk')->count(),
            'lost' => $users->where('rfm_segment', 'lost')->count(),
            'new' => $users->where('rfm_segment', 'new')->count(),
        ];

        $totalUsers = $users->count();

        // Average scores
        $avgRecency = $users->avg('rfm_recency_score') ?? 0;
        $avgFrequency = $users->avg('rfm_frequency_score') ?? 0;
        $avgMonetary = $users->avg('rfm_monetary_score') ?? 0;

        // Last segmentation date
        $lastSegmented = $users->max('rfm_segmented_at');

        return view('back.segmentation.index', compact(
            'users',
            'distribution',
            'totalUsers',
            'avgRecency',
            'avgFrequency',
            'avgMonetary',
            'lastSegmented',
        ));
    }

    /**
     * Run segmentation now.
     */
    public function run(RfmSegmentationService $service)
    {
        $result = $service->segmentAllUsers();

        $count = count($result['segments']);

        return redirect()->route('admin.segmentation.index')
            ->with('status', "✅ Segmentation RFM exécutée avec succès ! {$count} utilisateur(s) analysé(s) via " . ($result['metrics']['method'] ?? 'unknown') . ".");
    }

    /**
     * Send re-engagement notifications to at-risk and lost users.
     */
    public function notify()
    {
        $users = User::where('role', '!=', 'admin')
            ->whereIn('rfm_segment', ['at_risk', 'lost'])
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $user->notify(new \App\Notifications\AtRiskUserNotification(
                $user->rfm_segment,
                [
                    'segment' => $user->rfm_segment,
                    'details' => [
                        'days_since_last' => $user->rfm_days_since_last,
                        'rental_count' => $user->rfm_rental_count,
                        'total_spent' => $user->rfm_total_spent,
                    ],
                ]
            ));
            $count++;
        }

        return redirect()->route('admin.segmentation.index')
            ->with('status', "📧 {$count} notification(s) de ré-engagement envoyée(s) aux utilisateurs à risque / perdus.");
    }
}

