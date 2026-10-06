{{-- Conversation between the reporter and the admins (front and back office). --}}
@php($viewer = auth()->user())
<section id="conversation">
<div class="chat-thread" id="chat-thread" role="log" aria-live="polite" aria-label="Conversation" data-poll-url="{{ route('incidents.messages.index', $incident) }}" data-last-id="{{ $messages->last()?->id ?? 0 }}">
@forelse($messages as $chat)
<div class="chat-bubble {{ (int) $chat->user_id === (int) $viewer->id ? 'mine' : 'theirs' }}"><small class="chat-meta">{{ $chat->user->name }} · {{ $chat->created_at->format('d/m/Y H:i') }}</small><p>{{ $chat->body }}</p></div>
@empty
<p class="chat-empty" id="chat-empty">Aucun message pour le moment.@unless($incident->isClosed()) Écrivez le premier !@endunless</p>
@endforelse
</div>
<div class="alert alert-secondary mt-3 mb-0" id="chat-closed" role="status" @unless($incident->isClosed()) hidden @endunless><i class="fa fa-lock me-2" aria-hidden="true"></i>Cet incident est clôturé : la conversation est en lecture seule.</div>
@unless($incident->isClosed())
<form method="POST" action="{{ route('incidents.messages.store', $incident) }}" class="chat-form" id="chat-form">@csrf
<label class="visually-hidden" for="chat-body">Votre message</label>
<textarea class="form-control" id="chat-body" name="body" rows="2" maxlength="2000" required placeholder="Écrivez votre message…" title="Entrée pour envoyer, Maj + Entrée pour aller à la ligne">{{ old('body') }}</textarea>
<button class="btn btn-primary px-4" type="submit"><i class="fa fa-paper-plane me-2" aria-hidden="true"></i>Envoyer</button>
</form>
@endunless
<p class="chat-error" id="chat-error" role="alert" @unless($errors->has('body')) hidden @endunless>{{ $errors->first('body') }}</p>
</section>
<script>
// Live feel without WebSockets: poll for new messages every 5 s, send with fetch.
// Without JavaScript the form posts normally and the page reloads.
(function () {
    const thread = document.getElementById('chat-thread');
    const form = document.getElementById('chat-form');
    const error = document.getElementById('chat-error');
    let lastId = Number(thread.dataset.lastId);

    function addMessage(message) {
        if (message.id <= lastId) return;
        lastId = message.id;
        const empty = document.getElementById('chat-empty');
        if (empty) empty.remove();
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble ' + (message.mine ? 'mine' : 'theirs');
        const meta = document.createElement('small');
        meta.className = 'chat-meta';
        meta.textContent = message.author + ' · ' + message.time;
        const body = document.createElement('p');
        body.textContent = message.body; // never innerHTML
        bubble.append(meta, body);
        thread.append(bubble);
        thread.scrollTop = thread.scrollHeight;
    }

    function closeConversation() {
        if (form) form.remove();
        document.getElementById('chat-closed').hidden = false;
    }

    function showError(text) {
        error.textContent = text;
        error.hidden = false;
    }

    function poll() {
        fetch(thread.dataset.pollUrl + '?after=' + lastId, { headers: { Accept: 'application/json' } })
            .then(response => response.ok ? response.json() : null)
            .then(data => {
                if (!data) return;
                data.messages.forEach(addMessage);
                if (data.closed) closeConversation();
            })
            .catch(() => {});
    }

    thread.scrollTop = thread.scrollHeight;
    setInterval(poll, 5000);
    if (!form) return;

    const field = form.querySelector('textarea');
    const button = form.querySelector('button');
    field.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (!field.value.trim()) return;
        button.disabled = true;
        error.hidden = true;
        fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) })
            .then(response => response.json().catch(() => ({})).then(data => ({ response, data })))
            .then(({ response, data }) => {
                if (response.ok) {
                    addMessage(data.message);
                    field.value = '';
                } else if (response.status === 429) {
                    showError('Vous envoyez beaucoup de messages : patientez une minute.');
                } else if (response.status === 419) {
                    showError('Votre session a expiré. Actualisez la page.');
                } else {
                    showError(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Envoi impossible. Réessayez.'));
                    if (data.closed) closeConversation();
                }
            })
            .catch(() => showError('Connexion perdue. Réessayez.'))
            .finally(() => { button.disabled = false; field.focus(); });
    });
})();
</script>
