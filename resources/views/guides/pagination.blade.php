@if ($paginator->hasPages())
    <nav aria-label="Guide pages" class="flex flex-wrap items-center justify-between gap-4 text-sm text-[#a8a8bc]">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="opacity-50">← Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="text-[#a5b4fc]">← Previous</a>
        @endif
        <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="text-[#a5b4fc]">Next →</a>
        @else
            <span aria-disabled="true" class="opacity-50">Next →</span>
        @endif
    </nav>
@endif
