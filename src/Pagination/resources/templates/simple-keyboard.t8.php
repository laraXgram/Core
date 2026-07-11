@keyboard('inline')
@row

{{-- Previous page --}}
@if (! $paginator->onFirstPage())
@col($paginator->resolvedPreviousText(), callback_data: $paginator->previousPageData())
@endif

{{-- Next page --}}
@if ($paginator->hasMorePages())
@col($paginator->resolvedNextText(), callback_data: $paginator->nextPageData())
@endif

@endrow
@endkeyboard
