<?php

namespace LaraGram\Pagination;

class PaginationState
{
    /**
     * Bind the pagination state resolvers using the given application container as a base.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function resolveUsing($app)
    {
        Paginator::viewFactoryResolver(fn () => $app['view']);

        Paginator::currentPathResolver(fn () => $app['http.request']->url());

        Paginator::currentPageResolver(function ($pageName = 'page') use ($app) {
            $page = $app['http.request']->input($pageName);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return (int) $page;
            }

            return 1;
        });

        Paginator::queryStringResolver(fn () => $app['http.request']->query());

        CursorPaginator::currentCursorResolver(function ($cursorName = 'cursor') use ($app) {
            return Cursor::fromEncoded($app['http.request']->input($cursorName));
        });

        static::resolveTelegramUsing($app);
    }

    /**
     * Bind the Telegram pagination resolvers using the given application container.
     *
     * The current page is resolved from the incoming callback query data, which
     * has the shape "paginate:<key>:<page>". Each paginator matches only its own
     * key, so multiple paginators may coexist within a single update.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    protected static function resolveTelegramUsing($app)
    {
        $callback = fn () => static::callbackQuery($app);

        $currentPage = function ($key = 'page') use ($app) {
            $data = static::callbackQuery($app)['data'] ?? null;

            if ($data !== null && preg_match('/^'.TelegramPaginator::PREFIX.':'.preg_quote($key, '/').':(\d+)$/', $data, $matches)) {
                return max(1, (int) $matches[1]);
            }

            return 1;
        };

        foreach ([TelegramPaginator::class, TelegramLengthAwarePaginator::class] as $paginator) {
            $paginator::templateFactoryResolver(fn () => $app['template']);
            $paginator::templateCompilerResolver(fn () => $app['temple8.compiler']);
            $paginator::telegramCurrentPageResolver($currentPage);
            $paginator::telegramCallbackResolver($callback);
        }
    }

    /**
     * Get the callback query of the current update, if any.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return array{data: string|null, message_id: int|null}
     */
    protected static function callbackQuery($app)
    {
        try {
            $query = $app['request']->callback_query ?? null;
        } catch (\Throwable) {
            return ['data' => null, 'message_id' => null];
        }

        return [
            'data' => $query->data ?? null,
            'message_id' => $query->message->message_id ?? null,
        ];
    }
}
