@props(['method'])

@php
$type = match ($method) {
    'GET', 'OPTIONS', 'ANY' => 'default',
    'POST' => 'success',
    'PUT', 'PATCH' => 'primary',
    'DELETE' => 'error',
    default => 'default',
};
@endphp

<x-laragram-exceptions-renderer::badge type="{{ $type }}">
    <x-laragram-exceptions-renderer::icons.globe class="w-2.5 h-2.5" />
    {{ $method }}
</x-laragram-exceptions-renderer::badge>
