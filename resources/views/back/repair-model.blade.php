@extends('layouts.back')
@section('title', 'Modèle ML — délai de réparation')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
<div><span class="section-kicker">MACHINE LEARNING · HORS LIGNE</span><h2 class="mb-0">Prédiction du délai de réparation</h2></div>
<a href="{{ route('admin.maintenances.index') }}" class="btn btn-outline-secondary btn-sm">← Retour aux maintenances</a>
</div>

@if(!$metrics)
<div class="surface"><div class="empty-state"><i class="fa fa-brain d-block" aria-hidden="true"></i><h3>Le modèle n’est pas encore entraîné.</h3><p>Lancez <code>php artisan repair:train</code> pour l’entraîner.</p></div></div>
@else
<div class="surface mb-4">
<p class="mb-2">Quand un client signale un incident, le modèle prédit en combien de jours la réparation sera terminée, à partir des <strong>mots de la description</strong>, de la gravité, de la catégorie, du type d’intervention et de l’historique de l’équipement.</p>
<p class="small text-muted mb-0"><strong>{{ $metrics['model_name'] }}</strong> ({{ $metrics['n_estimators'] }} arbres) · fonctionne hors ligne, sans clé API · entraîné le {{ \Illuminate\Support\Carbon::parse($metrics['trained_at'])->format('d/m/Y à H:i') }}</p>
</div>

<div class="row g-3 mb-4">
@foreach([
    ['MAE', number_format($metrics['mae'], 2, ',', ' ').' j', 'Erreur moyenne : en moyenne, la prédiction se trompe de '.number_format($metrics['mae'], 1, ',', ' ').' jour(s).'],
    ['R²', number_format($metrics['r2'], 2, ',', ' '), 'Le modèle explique '.round($metrics['r2'] * 100).' % des différences de durée (1 = parfait).'],
    ['Précision ± 1 jour', $metrics['within_1_day'].' %', 'Prédictions à 1 jour près ('.$metrics['within_2_days'].' % à 2 jours près).'],
    ['Gain vs sans ML', '−'.$metrics['improvement_vs_action_baseline'].' %', 'Erreur réduite par rapport à la moyenne par type d’intervention.'],
] as [$label, $value, $help])
<div class="col-6 col-xl-3"><div class="surface h-100 ml-kpi"><small class="text-muted d-block">{{ $label }}</small><strong>{{ $value }}</strong><p class="small text-muted mb-0">{{ $help }}</p></div></div>
@endforeach
</div>

<div class="row g-4 mb-4">
<div class="col-lg-6"><div class="surface h-100">
<span class="section-kicker">COMPARAISON</span><h3 class="h5 mt-2 mb-3">Erreur moyenne (MAE, plus bas = mieux)</h3>
@php($maxMae = max($metrics['baseline_global_mae'], $metrics['baseline_action_mae'], $metrics['mae']))
@foreach([['Moyenne globale (sans ML)', $metrics['baseline_global_mae'], false], ['Moyenne par type d’intervention (sans ML)', $metrics['baseline_action_mae'], false], ['Notre modèle', $metrics['mae'], true]] as [$label, $value, $ours])
<div class="ml-bar-row"><div class="d-flex justify-content-between small"><span @class(['fw-bold' => $ours])>{{ $label }}</span><span>{{ number_format($value, 2, ',', ' ') }} j</span></div><div class="ml-bar"><span @class(['is-ours' => $ours]) style="width: {{ round($value / $maxMae * 100) }}%"></span></div></div>
@endforeach
<dl class="detail-list mt-3 mb-0 small">
<dt>RMSE</dt><dd>{{ number_format($metrics['rmse'], 2, ',', ' ') }} j <span class="text-muted">— pénalise davantage les grosses erreurs</span></dd>
<dt>Validation croisée (5 plis)</dt><dd class="mb-0">MAE {{ number_format($metrics['cv_mae_mean'], 2, ',', ' ') }} ± {{ number_format($metrics['cv_mae_std'], 2, ',', ' ') }} j <span class="text-muted">— résultat stable, pas de sur-apprentissage</span></dd>
</dl>
</div></div>
<div class="col-lg-6"><div class="surface h-100">
<span class="section-kicker">CE QUI COMPTE</span><h3 class="h5 mt-2 mb-3">Importance des variables</h3>
@php($maxGroup = max($metrics['feature_groups']) ?: 1)
@foreach($metrics['feature_groups'] as $label => $value)
<div class="ml-bar-row"><div class="d-flex justify-content-between small"><span>{{ $label }}</span><span>{{ number_format($value, 1, ',', ' ') }} %</span></div><div class="ml-bar"><span style="width: {{ round($value / $maxGroup * 100) }}%"></span></div></div>
@endforeach
<p class="small text-muted mt-3 mb-2">Mots les plus déterminants (TF-IDF) :</p>
<div class="d-flex flex-wrap gap-2">@foreach($metrics['top_words'] as $word => $value)<span class="ml-word">{{ $word }} <small>{{ number_format($value, 1, ',', ' ') }} %</small></span>@endforeach</div>
</div></div>
</div>

<div class="surface mb-4">
<span class="section-kicker">EXEMPLES DU JEU DE TEST</span><h3 class="h5 mt-2 mb-3">Prédiction vs réalité</h3>
<div class="table-responsive"><table class="table office-table mb-0">
<thead><tr><th>Description</th><th>Catégorie</th><th>Gravité</th><th class="text-end">Réel</th><th class="text-end">Prédit</th><th class="text-end">Écart</th></tr></thead>
<tbody>@foreach($metrics['examples'] as $example)
<tr><td class="notes">{{ $example['description'] }}</td><td class="text-nowrap">{{ $example['category'] }}</td><td><span class="incident-badge severity-{{ $example['severity'] }}">{{ \App\Models\Incident::SEVERITIES[$example['severity']] ?? $example['severity'] }}</span></td><td class="text-end">{{ $example['real'] }} j</td><td class="text-end fw-bold">{{ number_format($example['predicted'], 1, ',', ' ') }} j</td><td class="text-end text-muted">{{ number_format(abs($example['predicted'] - $example['real']), 1, ',', ' ') }}</td></tr>
@endforeach</tbody></table></div>
</div>

<div class="surface">
<span class="section-kicker">DONNÉES</span><h3 class="h5 mt-2 mb-3">Jeu d’entraînement</h3>
<dl class="detail-list mb-3">
<dt>Historique simulé</dt><dd>{{ $metrics['simulated_samples'] }} réparations</dd>
<dt>Historique réel</dt><dd>{{ $metrics['real_samples'] }} réparations utilisées · {{ $realRepairs }} disponibles en base</dd>
<dt>Découpage</dt><dd class="mb-0">{{ $metrics['train_samples'] }} pour l’entraînement · {{ $metrics['test_samples'] }} pour le test (80 / 20)</dd>
</dl>
<p class="small text-muted mb-0">Chaque maintenance créée depuis un incident avec sa <strong>durée réelle</strong> enrichit l’historique. Pour réentraîner le modèle : <code>php artisan repair:train</code></p>
</div>
@endif
@endsection
