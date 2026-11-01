@extends('layouts.back')
@section('title', 'Réclamation #'.$reclamation->id)
@section('content')
<a href="{{ route('admin.reclamations.index') }}" class="text-primary">← Retour aux réclamations</a>
<div class="surface p-4 mt-3"><div class="d-flex justify-content-between"><div><span class="section-kicker">{{ $reclamation->typeLabel() }}</span><h2>{{ $reclamation->subject }}</h2><p class="text-muted">Par {{ $reclamation->user->name }} ({{ $reclamation->user->email }}) · {{ $reclamation->reclamation_date->format('d/m/Y') }}</p></div><span class="reclamation-status {{ $reclamation->statusClass() }}">{{ $reclamation->statusLabel() }}</span></div>
<hr>@if($reclamation->type === 'avis')<div class="alert alert-light"><strong>Auteur de l’avis concerné :</strong> {{ $reclamation->review_author_name }}</div>@endif<p style="white-space:pre-line">{{ $reclamation->description }}</p>
<form method="POST" action="{{ route('admin.reclamations.status', $reclamation) }}" class="mt-4">@csrf @method('PATCH')<div class="mb-3"><label class="form-label fw-bold" for="status">Statut</label><select id="status" name="status" class="form-select">@foreach(\App\Models\Reclamation::STATUSES as $value => $label)<option value="{{ $value }}" @selected($reclamation->status === $value)>{{ $label }}</option>@endforeach</select></div><div class="mb-3"><div class="d-flex justify-content-between align-items-center gap-2"><label class="form-label fw-bold mb-0" for="admin_response">Réponse au client <span class="fw-normal text-muted">(facultatif)</span></label><button id="generate-response-draft" class="btn btn-outline-primary btn-sm" type="button"><i class="fas fa-magic me-1" aria-hidden="true"></i>Générer avec l’IA</button></div><textarea id="admin_response" name="admin_response" rows="5" maxlength="5000" class="form-control mt-2" placeholder="Écrivez une réponse visible par le client...">{{ old('admin_response', $reclamation->admin_response) }}</textarea><div id="response-draft-status" class="form-text" role="status">Le brouillon sera ajouté ici pour que vous puissiez le modifier avant l’envoi.</div></div><button class="btn btn-primary">Enregistrer le statut et la réponse</button></form>
</div>
<script>
document.getElementById('generate-response-draft').addEventListener('click', async function () {
    const button = this;
    const textarea = document.getElementById('admin_response');
    const status = document.getElementById('response-draft-status');
    button.disabled = true;
    status.textContent = 'Génération du brouillon…';

    try {
        const response = await fetch('{{ route('admin.reclamations.response-draft', $reclamation) }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Le brouillon n’a pas pu être généré.');
        }
        textarea.value = data.draft;
        textarea.focus();
        status.textContent = 'Brouillon généré (' + (data.source === 'ia' ? 'OpenAI' : 'règles locales') + '). Relisez-le avant de l’enregistrer.';
    } catch (error) {
        status.textContent = error.message;
        status.classList.add('text-danger');
    } finally {
        button.disabled = false;
    }
});
</script>
@endsection
