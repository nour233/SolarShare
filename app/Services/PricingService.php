<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\PricingRule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PricingService
{
    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Calculate the full pricing breakdown for a rental.
     */
    public function calculate(Equipment $equipment, Carbon $start, Carbon $end): array
    {
        $rules = PricingRule::allCached();

        $days        = $start->diffInDays($end) + 1;
        $pricePerDay = (float) $equipment->price_per_day;

        // Weekend surcharge
        $weekendRate   = $rules['weekend_surcharge'] ?? 0.15;
        $weekendDays   = $this->countWeekendDays($start, $end);
        $weekdayDays   = $days - $weekendDays;

        $weekdayTotal     = $weekdayDays * $pricePerDay;
        $weekendTotal     = $weekendDays * $pricePerDay * (1 + $weekendRate);
        $weekendSurcharge = $weekendDays * $pricePerDay * $weekendRate;

        $subtotal = $weekdayTotal + $weekendTotal;

        // Peak season surcharge
        $peakRate      = $rules['peak_surcharge'] ?? 0.10;
        $peakMonths    = $this->peakMonths($rules);
        $peakDays      = $this->countPeakDays($start, $end, $peakMonths);
        $peakSurcharge = $peakDays * $pricePerDay * $peakRate;
        $subtotal     += $peakSurcharge;

        // Duration discount
        $discountRate   = $this->discountRate($days, $rules);
        $discountAmount = round($subtotal * $discountRate, 2);
        $total          = round($subtotal - $discountAmount, 2);

        $breakdown = $this->buildBreakdown(
            $equipment, $days, $weekdayDays, $weekendDays, $weekendRate,
            $peakDays, $peakRate, $peakSurcharge,
            $discountRate, $discountAmount, $total
        );

        return [
            'days'              => $days,
            'base_price'        => round($days * $pricePerDay, 2),
            'weekend_days'      => $weekendDays,
            'weekend_surcharge' => round($weekendSurcharge, 2),
            'peak_days'         => $peakDays,
            'peak_surcharge'    => round($peakSurcharge, 2),
            'discount_rate'     => $discountRate,
            'discount_amount'   => $discountAmount,
            'total_price'       => $total,
            'breakdown'         => $breakdown,
        ];
    }

    /** Quick helper — just the final price. */
    public function totalPrice(Equipment $equipment, Carbon $start, Carbon $end): float
    {
        return $this->calculate($equipment, $start, $end)['total_price'];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function countWeekendDays(Carbon $start, Carbon $end): int
    {
        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if ($day->isWeekend()) {
                $count++;
            }
        }
        return $count;
    }

    private function peakMonths(array $rules): array
    {
        // peak_months stored as a value (6) is just a placeholder.
        // We read peak_month_1 … peak_month_12 flags if they exist,
        // otherwise fall back to [6, 7, 8].
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = 'peak_month_' . $m;
            if (isset($rules[$key]) && $rules[$key] > 0) {
                $months[] = $m;
            }
        }
        return $months ?: [6, 7, 8];
    }

    private function countPeakDays(Carbon $start, Carbon $end, array $peakMonths): int
    {
        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if (in_array($day->month, $peakMonths, true)) {
                $count++;
            }
        }
        return $count;
    }

    private function discountRate(int $days, array $rules): float
    {
        // Build tiers from DB rules sorted descending by days threshold
        $tiers = [
            30 => (float) ($rules['discount_30_days'] ?? 0.20),
            14 => (float) ($rules['discount_14_days'] ?? 0.15),
            7  => (float) ($rules['discount_7_days']  ?? 0.10),
        ];

        foreach ($tiers as $minDays => $rate) {
            if ($days >= $minDays && $rate > 0) {
                return $rate;
            }
        }
        return 0.0;
    }

    private function buildBreakdown(
        Equipment $equipment,
        int $days,
        int $weekdayDays,
        int $weekendDays,
        float $weekendRate,
        int $peakDays,
        float $peakRate,
        float $peakSurcharge,
        float $discountRate,
        float $discountAmount,
        float $total
    ): array {
        $p = (float) $equipment->price_per_day;
        $lines = [];

        if ($weekdayDays > 0) {
            $lines[] = [
                'label'  => $weekdayDays . ' jour(s) semaine × ' . number_format($p, 2, ',', ' ') . ' €',
                'amount' => round($weekdayDays * $p, 2),
                'type'   => 'base',
            ];
        }

        if ($weekendDays > 0) {
            $lines[] = [
                'label'  => $weekendDays . ' jour(s) week-end × ' . number_format($p * (1 + $weekendRate), 2, ',', ' ') . ' € (+' . round($weekendRate * 100) . '%)',
                'amount' => round($weekendDays * $p * (1 + $weekendRate), 2),
                'type'   => 'surcharge',
            ];
        }

        if ($peakDays > 0) {
            $lines[] = [
                'label'  => 'Supplément haute saison (' . $peakDays . ' j · +' . round($peakRate * 100) . '%)',
                'amount' => round($peakSurcharge, 2),
                'type'   => 'surcharge',
            ];
        }

        if ($discountRate > 0) {
            $lines[] = [
                'label'  => 'Réduction longue durée (−' . round($discountRate * 100) . '% · ' . $days . ' jours)',
                'amount' => -$discountAmount,
                'type'   => 'discount',
            ];
        }

        $lines[] = [
            'label'  => 'Total',
            'amount' => $total,
            'type'   => 'total',
        ];

        return $lines;
    }
}
