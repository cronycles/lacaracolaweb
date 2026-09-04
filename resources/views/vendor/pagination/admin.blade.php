{{-- Custom pagination view for the admin panel (plain CSS, no Tailwind utilities required) --}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="pagination__info">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <strong>{{ $paginator->firstItem() }}</strong> {!! __('to') !!} <strong>{{ $paginator->lastItem() }}</strong>
            @else
                {{ $paginator->count() }}
            @endif
            {!! __('of') !!} <strong>{{ $paginator->total() }}</strong> {!! __('results') !!}
        </p>

        <ul class="pagination__list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li><span class="pagination__link pagination__link--disabled" aria-hidden="true">&lsaquo;</span></li>
            @else
                <li><a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">&lsaquo;</a></li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li><span class="pagination__link pagination__link--dots">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="pagination__link pagination__link--current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="pagination__link" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li><a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">&rsaquo;</a></li>
            @else
                <li><span class="pagination__link pagination__link--disabled" aria-hidden="true">&rsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
