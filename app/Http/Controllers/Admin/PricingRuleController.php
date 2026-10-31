<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PricingRuleController extends Controller
{
    public function index()
    {
        $groups = PricingRule::orderBy('group')->orderBy('id')->get()->groupBy('group');

        return view('back.pricing.index', compact('groups'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'rules'          => ['required', 'array'],
            'rules.*.value'  => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($request->input('rules') as $id => $data) {
            PricingRule::where('id', $id)->update([
                'value' => (float) $data['value'] / 100,  // store as decimal (15 → 0.15)
            ]);
        }

        // Peak months: checkboxes → store as individual keys
        $selectedMonths = $request->input('peak_months', []);
        for ($m = 1; $m <= 12; $m++) {
            $key    = 'peak_month_' . $m;
            $exists = PricingRule::where('key', $key)->exists();
            $active = in_array((string) $m, $selectedMonths, true) ? 1 : 0;

            if ($exists) {
                PricingRule::where('key', $key)->update(['value' => $active]);
            } else {
                PricingRule::create([
                    'key'   => $key,
                    'label' => 'Mois ' . $m . ' (haute saison)',
                    'group' => 'peak',
                    'value' => $active,
                    'unit'  => 'flag',
                ]);
            }
        }

        Cache::forget('pricing_rules');

        return back()->with('status', 'Règles de tarification mises à jour.');
    }
}
