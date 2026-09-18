<!-- !component! -->

{{-- The navigation keyboard of a simple (previous / next) Telegram paginator. --}}

@keyboard('inline')

@if (! is_null($paginator->direction()))
@keyboardDirection($paginator->direction())
@endif

@row
@if (! $paginator->onFirstPage())
@col($paginator->resolvedPreviousText(), callback_data: $paginator->previousPageData())
@endif
@if ($paginator->resolvedIndicator())
@col($paginator->resolvedIndicator(), callback_data: $paginator->pageData($paginator->currentPage()))
@endif
@if ($paginator->hasMorePages())
@col($paginator->resolvedNextText(), callback_data: $paginator->nextPageData())
@endif
@endrow

@endkeyboard
