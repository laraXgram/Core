{{-- The default message of a Telegram paginator. --}}

@chat_id($chat_id ?? chat()->id)

@isset($method)
@method($method)
@endisset

@text
@if ($paginator->heading())
{{ $paginator->heading() }}

@endif
@forelse ($paginator as $key => $item)
{{ $paginator->format($item, $key) }}
@empty
{{ function_exists('__') && __('pagination.empty') !== 'pagination.empty' ? __('pagination.empty') : 'No results.' }}
@endforelse
@endtext

@paginate($paginator)
