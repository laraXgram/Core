@chat_id($chat_id ?? chat()->id)
@isset($method)
    @method($method)
@endisset()

@text
@forelse($paginator as $item)
{{ is_scalar($item) ? $item : ($item->title ?? $item->name ?? $item->id ?? json_encode($item)) }}
@empty
{{ function_exists('__') && __('pagination.empty') !== 'pagination.empty' ? __('pagination.empty') : 'No results.' }}
@endforelse
@endtext

@reply_markup($paginator->keyboard())
