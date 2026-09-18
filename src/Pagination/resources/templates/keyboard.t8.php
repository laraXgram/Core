<!-- !component! -->

{{-- The navigation keyboard of a numbered Telegram paginator. --}}

@keyboard('inline')

@if (! is_null($paginator->direction()))
@keyboardDirection($paginator->direction())
@endif

@row
@foreach ($paginator->pages() as $page)
@col($page == $paginator->currentPage() ? "· {$page} ·" : (string) $page, callback_data: $paginator->pageData($page))
@endforeach
@endrow

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
