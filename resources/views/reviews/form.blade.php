@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="compose-grid">
<form method="POST" action="{{ $action }}" class="compose-form" id="review-compose-form">@csrf @if($method !== 'POST') @method($method) @endif
    <fieldset class="compose-section"><legend><span>01</span> Quel service avez-vous utilisé ?</legend>
    <div class="compose-services">@foreach(\App\Models\Review::SERVICES as $value => $label)
        <label><input type="radio" name="service" value="{{ $value }}" data-label="{{ $label }}" required @checked(old('service', $review?->service) === $value)><span><i class="fas {{ ['equipment'=>'fa-solar-panel','rental'=>'fa-calendar-check','maintenance'=>'fa-tools','delivery'=>'fa-truck','general'=>'fa-comment-dots'][$value] }}" aria-hidden="true"></i>{{ $label }}</span></label>
    @endforeach</div></fieldset>
    <fieldset class="compose-section"><legend><span>02</span> Comment était votre expérience ?</legend>
    <div class="compose-rating">@for($star = 1; $star <= 5; $star++)<label><input type="radio" name="rating" value="{{ $star }}" required @checked((int) old('rating', $review?->rating ?? 5) === $star)><span aria-hidden="true">★</span><span class="visually-hidden">{{ $star }} étoile{{ $star > 1 ? 's' : '' }}</span></label>@endfor<span class="compose-rating-caption" aria-live="polite"></span></div>
    </fieldset>
    <div class="compose-section compose-writing"><h2><span>03</span> Racontez-nous</h2>
    <label for="title">Votre expérience en quelques mots</label><input id="title" name="title" value="{{ old('title', $review?->title) }}" class="form-control" maxlength="120" required placeholder="Ex. Une batterie idéale pour notre week-end">
    <label for="comment">Votre avis</label><textarea id="comment" name="comment" rows="5" minlength="10" maxlength="2000" class="form-control" required placeholder="Qu’avez-vous apprécié ? Que pourrions-nous améliorer ?">{{ old('comment', $review?->comment) }}</textarea>
    <div class="compose-writing-meta"><span>10 caractères minimum</span><span id="review-character-count">0 / 2 000</span></div></div>
    <div class="compose-footer"><span><i class="far fa-eye" aria-hidden="true"></i> Votre avis sera public.</span><button class="btn btn-primary" type="submit">{{ $method === 'POST' ? 'Publier mon avis' : 'Enregistrer' }} <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></button></div>
</form>
<aside class="compose-aside">
    <div class="compose-preview"><span class="compose-preview-label">APERÇU DE VOTRE AVIS</span><div class="compose-preview-stars" aria-hidden="true">★★★★★</div><span class="compose-preview-service">Votre service</span><h3 class="compose-preview-title">Votre expérience, vos mots.</h3><p class="compose-preview-comment">Votre avis apparaîtra ici au fil de votre saisie.</p><div class="compose-preview-author"><span aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>Aperçu avant publication</small></div></div></div>
    <div class="compose-tips"><i class="far fa-lightbulb" aria-hidden="true"></i><h3>Un avis qui aide vraiment</h3><p>Décrivez le service utilisé, ce qui a bien fonctionné et un éventuel point à améliorer.</p><small>Restez précis et respectueux. Évitez les coordonnées personnelles dans votre texte.</small></div>
</aside>
</div>
<script>
(() => {
 const form = document.getElementById('review-compose-form');
 const preview = document.querySelector('.compose-preview');
 const captions = ['Très décevante', 'À améliorer', 'Correcte', 'Très bonne', 'Excellente !'];
 function update() {
  const rating = Number(form.querySelector('[name="rating"]:checked')?.value || 0);
  form.querySelectorAll('.compose-rating label').forEach((label, index) => label.classList.toggle('is-filled', index < rating));
  form.querySelector('.compose-rating-caption').textContent = captions[rating - 1] || 'Choisissez une note';
  preview.querySelector('.compose-preview-stars').textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating);
  preview.querySelector('.compose-preview-service').textContent = form.querySelector('[name="service"]:checked')?.dataset.label || 'Votre service';
  preview.querySelector('.compose-preview-title').textContent = form.elements.title.value.trim() || 'Votre expérience, vos mots.';
  preview.querySelector('.compose-preview-comment').textContent = form.elements.comment.value.trim() || 'Votre avis apparaîtra ici au fil de votre saisie.';
  document.getElementById('review-character-count').textContent = form.elements.comment.value.length + ' / 2 000';
 }
 form.addEventListener('input', update); form.addEventListener('change', update); update();
})();
</script>
