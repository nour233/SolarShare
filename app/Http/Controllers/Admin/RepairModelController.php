<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Services\RepairTimePredictor;

class RepairModelController extends Controller
{
    /** "Performance du modèle": metrics of the repair time model. */
    public function __invoke()
    {
        return view('back.repair-model', [
            'metrics' => RepairTimePredictor::metrics(),
            'realRepairs' => Maintenance::whereNotNull('incident_id')->whereNotNull('duration_days')->count(),
        ]);
    }
}
