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

        Paginator::currentPathResolver(fn () => $app['request']->url());

        Paginator::currentPageResolver(function ($pageName = 'page') use ($app) {
            $page = $app['request']->input($pageName);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return (int) $page;
            }

            return 1;
        });

        Paginator::queryStringResolver(fn () => $app['request']->query());

        CursorPaginator::currentCursorResolver(function ($cursorName = 'cursor') use ($app) {
            return Cursor::fromEncoded($app['request']->input($cursorName));
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
        $templateFactory = fn () => $app['template'];

        $currentPage = function ($key = 'page') use ($app) {
            $data = static::callbackQueryData($app);

            if ($data !== null && preg_match('/^paginate:'.preg_quote($key, '/').':(\d+)$/', $data, $matches)) {
                return max(1, (int) $matches[1]);
            }

            return 1;
        };

        foreach ([TelegramPaginator::class, TelegramLengthAwarePaginator::class] as $paginator) {
            $paginator::templateFactoryResolver($templateFactory);
            $paginator::telegramCurrentPageResolver($currentPage);
        }
    }

    /**
     * Get the callback query data from the current update, if any.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return string|null
     */
    protected static function callbackQueryData($app)
    {
        try {
            return $app['request']->callback_query->data ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
