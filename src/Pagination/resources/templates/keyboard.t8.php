@keyboard('inline')
@row

{{-- Previous page --}}
@if (! $paginator->onFirstPage())
@col($paginator->resolvedPreviousText(), callback_data: $paginator->previousPageData())
@endif

{{-- Numbered pages ($elements: arrays of [page => callback_data] or "..." separators) --}}
@foreach ($elements as $element)
@if (is_array($element))
@foreach ($element as $page => $data)
@col($page == $paginator->currentPage() ? "· {$page} ·" : (string) $page, callback_data: $data)
@endforeach
@endif
@endforeach

{{-- Next page --}}
@if ($paginator->hasMorePages())
@col($paginator->resolvedNextText(), callback_data: $paginator->nextPageData())
@endif

@endrow
@endkeyboard
