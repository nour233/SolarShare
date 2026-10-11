@if($equipments->total() > 0)
<div class="equipment-pagination">
<p><strong>{{ $equipments->firstItem() }}–{{ $equipments->lastItem() }}</strong> sur {{ $equipments->total() }} équipements</p>
@if($equipments->hasPages())
<nav aria-label="Pagination des équipements">
@if($equipments->onFirstPage())<span class="page-arrow disabled" aria-disabled="true">← Précédent</span>@else<a class="page-arrow" href="{{ $equipments->previousPageUrl() }}" rel="prev">← Précédent</a>@endif
@for($page = 1; $page <= $equipments->lastPage(); $page++)
@if($page === 1 || $page === $equipments->lastPage() || abs($page - $equipments->currentPage()) <= 1)
@if($page === $equipments->currentPage())<span class="page-number active" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>@else<a class="page-number" href="{{ $equipments->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>@endif
@elseif($page === $equipments->currentPage() - 2 || $page === $equipments->currentPage() + 2)<span class="page-dots">…</span>@endif
@endfor
@if($equipments->hasMorePages())<a class="page-arrow" href="{{ $equipments->nextPageUrl() }}" rel="next">Suivant →</a>@else<span class="page-arrow disabled" aria-disabled="true">Suivant →</span>@endif
</nav>
@endif
</div>
@endif
