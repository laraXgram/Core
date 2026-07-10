<x-laragram-exceptions-renderer::layout>
    <x-laragram-exceptions-renderer::section-container class="px-6 py-0 sm:py-0">
        <x-laragram-exceptions-renderer::topbar :title="$exception->title()" :markdown="$exceptionAsMarkdown" />
    </x-laragram-exceptions-renderer::section-container>

    <x-laragram-exceptions-renderer::separator />

    <x-laragram-exceptions-renderer::section-container class="flex flex-col gap-8 py-0 sm:py-0">
        <x-laragram-exceptions-renderer::header :$exception />
    </x-laragram-exceptions-renderer::section-container>

    <x-laragram-exceptions-renderer::separator class="-mt-5 -z-10" />

    <x-laragram-exceptions-renderer::section-container class="flex flex-col gap-8 pt-14">
        <x-laragram-exceptions-renderer::trace :$exception />

        @if ($exception->previousExceptions()->isNotEmpty())
            <x-laragram-exceptions-renderer::previous-exceptions :$exception />
        @endif

        <x-laragram-exceptions-renderer::query :queries="$exception->applicationQueries()" />
    </x-laragram-exceptions-renderer::section-container>

    <x-laragram-exceptions-renderer::separator />

    <x-laragram-exceptions-renderer::section-container class="flex flex-col gap-12">
        <x-laragram-exceptions-renderer::request-header :headers="$exception->requestHeaders()" />

        <x-laragram-exceptions-renderer::request-body :body="$exception->requestBody()" />

        <x-laragram-exceptions-renderer::routing :routing="$exception->applicationRouteContext()" />

        <x-laragram-exceptions-renderer::routing-parameter :routeParameters="$exception->applicationRouteParametersContext()" />
    </x-laragram-exceptions-renderer::section-container>

    <x-laragram-exceptions-renderer::separator />

</x-laragram-exceptions-renderer::layout>
