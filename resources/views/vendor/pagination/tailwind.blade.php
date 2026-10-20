@if ($paginator->hasPages())
    <nav class="pagination ui-pagination" aria-label="Navigasi halaman data">
        <p class="ui-pagination-summary">
            Menampilkan <strong>{{ \App\Support\UiText::number($paginator->firstItem() ?? 0) }}</strong>–<strong>{{ \App\Support\UiText::number($paginator->lastItem() ?? 0) }}</strong>
            dari <strong>{{ \App\Support\UiText::number($paginator->total()) }}</strong> data
            <span class="ui-pagination-position">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        </p>
        <div class="ui-pagination-pages">
            @if ($paginator->onFirstPage())
                <span class="ui-page" aria-disabled="true" aria-label="Halaman sebelumnya"><span aria-hidden="true">‹</span></span>
            @else
                <a class="ui-page" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"><span aria-hidden="true">‹</span></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="ui-page-gap" aria-hidden="true">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="ui-page" aria-current="page" aria-label="Halaman {{ $page }}, sedang dibuka">{{ $page }}</span>
                        @else
                            <a class="ui-page" href="{{ $url }}" aria-label="Buka halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="ui-page" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"><span aria-hidden="true">›</span></a>
            @else
                <span class="ui-page" aria-disabled="true" aria-label="Halaman berikutnya"><span aria-hidden="true">›</span></span>
            @endif
        </div>
    </nav>
@endif
