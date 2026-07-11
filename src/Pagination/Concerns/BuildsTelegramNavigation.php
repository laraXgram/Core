<?php

namespace LaraGram\Pagination\Concerns;

use Closure;

trait BuildsTelegramNavigation
{
    /**
     * The current page resolver callback (isolated from the web paginators).
     *
     * @var \Closure|null
     */
    protected static $telegramCurrentPageResolver;

    /**
     * The template factory resolver callback.
     *
     * @var \Closure|null
     */
    protected static $templateFactoryResolver;

    /**
     * The prefix used for every navigation callback_data value.
     *
     * @var string
     */
    protected $callbackPrefix = 'paginate';

    /**
     * The label used for the "previous page" button.
     *
     * @var string|null
     */
    protected $previousText;

    /**
     * The label used for the "next page" button.
     *
     * @var string|null
     */
    protected $nextText;

    /**
     * The default template used to render the paginated message.
     *
     * @var string
     */
    public static $defaultTelegramTemplate = 'pagination::telegram';

    /**
     * The default keyboard template for length-aware (numbered) paginators.
     *
     * @var string
     */
    public static $defaultKeyboardTemplate = 'pagination::keyboard';

    /**
     * The default keyboard template for simple (previous / next) paginators.
     *
     * @var string
     */
    public static $defaultSimpleKeyboardTemplate = 'pagination::simple-keyboard';

    /**
     * A per-instance override for the navigation keyboard template.
     *
     * @var string|null
     */
    protected $keyboardTemplate;

    /**
     * Get / set the callback key identifying this dataset.
     *
     * The key is embedded in every callback_data value and is what the
     * current-page resolver matches against. Keep it short (callback_data is
     * limited to 64 bytes) and unique per listener.
     *
     * @param  string|null  $key
     * @return ($key is null ? string : $this)
     */
    public function key($key = null)
    {
        if (is_null($key)) {
            return $this->pageName;
        }

        $this->pageName = $key;

        return $this;
    }

    /**
     * Set the "previous" and "next" button labels.
     *
     * @param  string|null  $previous
     * @param  string|null  $next
     * @return $this
     */
    public function labels($previous = null, $next = null)
    {
        if (! is_null($previous)) {
            $this->previousText = $previous;
        }

        if (! is_null($next)) {
            $this->nextText = $next;
        }

        return $this;
    }

    /**
     * Set the "previous" button label.
     *
     * @param  string  $text
     * @return $this
     */
    public function previousText($text)
    {
        $this->previousText = $text;

        return $this;
    }

    /**
     * Set the "next" button label.
     *
     * @param  string  $text
     * @return $this
     */
    public function nextText($text)
    {
        $this->nextText = $text;

        return $this;
    }

    /**
     * Build the callback_data value for the given page number.
     *
     * @param  int  $page
     * @return string
     */
    public function callbackData($page)
    {
        return $this->callbackPrefix.':'.$this->pageName.':'.$page;
    }

    /**
     * Get the navigation payload for a given page number.
     *
     * This is the Telegram counterpart of the web paginator's url($page): it
     * powers previousPageUrl()/nextPageUrl()/getUrlRange() and the UrlWindow, so
     * the entire web pagination machinery ($elements, previous/next, ...) works
     * unchanged but yields callback_data instead of URLs.
     *
     * @param  int  $page
     * @return string
     */
    public function url($page)
    {
        return $this->callbackData($page <= 0 ? 1 : $page);
    }

    /**
     * Alias of url(): the callback_data for the given page.
     *
     * @param  int  $page
     * @return string
     */
    public function pageData($page)
    {
        return $this->url($page);
    }

    /**
     * Get the callback_data for the previous page (null on the first page).
     *
     * @return string|null
     */
    public function previousPageData()
    {
        return $this->previousPageUrl();
    }

    /**
     * Get the callback_data for the next page (null on the last page).
     *
     * @return string|null
     */
    public function nextPageData()
    {
        return $this->nextPageUrl();
    }

    /**
     * Get a range of [page => callback_data] pairs.
     *
     * @param  int  $start
     * @param  int  $end
     * @return array<int, string>
     */
    public function getDataRange($start, $end)
    {
        return $this->getUrlRange($start, $end);
    }

    /**
     * Get the numbered "window" of page elements, exactly like the web
     * length-aware paginator. Each element is either an array of
     * [page => callback_data] or a "..." separator string. Simple paginators
     * (without a known last page) return an empty list.
     *
     * @return array
     */
    public function elements()
    {
        if (! $this->hasKnownLastPage()) {
            return [];
        }

        $window = \LaraGram\Pagination\UrlWindow::make($this);

        return array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }

    /**
     * Get the resolved "previous" button label.
     *
     * @return string
     */
    public function resolvedPreviousText()
    {
        return $this->previousText ?? $this->translate('pagination.previous', '« Previous');
    }

    /**
     * Translate the given key, falling back to the default when unavailable.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    protected function translate($key, $default)
    {
        if (! function_exists('__')) {
            return $default;
        }

        try {
            $value = __($key);
        } catch (\Throwable) {
            return $default;
        }

        return is_string($value) && $value !== $key ? $value : $default;
    }

    /**
     * Get the resolved "next" button label.
     *
     * @return string
     */
    public function resolvedNextText()
    {
        return $this->nextText ?? $this->translate('pagination.next', 'Next');
    }

    /**
     * Determine if the paginator knows its last page.
     *
     * @return bool
     */
    public function hasKnownLastPage()
    {
        return method_exists($this, 'lastPage');
    }

    /**
     * The compiled keyboard templates, keyed by "path:mtime".
     *
     * @var array<string, string>
     */
    protected static $compiledKeyboards = [];

    /**
     * Render the navigation keyboard from the keyboard template.
     *
     * The layout lives in a publishable template (default: the core
     * "pagination::keyboard"), which is a plain Temple8 @keyboard block. It is
     * evaluated on its own (never dispatched), and the reply_markup it builds is
     * captured and returned as JSON.
     *
     * @return string  The reply_markup JSON.
     */
    public function keyboard()
    {
        $factory = static::templateFactory();

        $name = $this->keyboardTemplate ?? ($this->hasKnownLastPage()
            ? static::$defaultKeyboardTemplate
            : static::$defaultSimpleKeyboardTemplate);

        $template = $factory->make($name, [
            'paginator' => $this,
            'elements' => $this->elements(),
        ]);

        $path = $template->getPath();
        $cacheKey = $path.':'.@filemtime($path);

        $php = static::$compiledKeyboards[$cacheKey]
            ??= app('temple8.compiler')->compileString(app('files')->get($path));

        return trim((string) static::evaluateKeyboard($php, $template->gatherData()));
    }

    /**
     * Evaluate compiled keyboard PHP in isolation and capture its reply_markup.
     *
     * @param  string  $__php
     * @param  array  $__data
     * @return string
     */
    protected static function evaluateKeyboard($__php, array $__data)
    {
        return (static function () use ($__php, $__data) {
            extract($__data);

            ob_start();

            try {
                eval('?>'.$__php);
            } finally {
                ob_end_clean();
            }

            return $__t8__reply_markup ?? '';
        })();
    }

    /**
     * Get the navigation keyboard as an array.
     *
     * @return array
     */
    public function toKeyboard()
    {
        return json_decode($this->keyboard(), true) ?: [];
    }

    /**
     * Set the template used to render the navigation keyboard.
     *
     * @param  string  $template
     * @return $this
     */
    public function keyboardTemplate($template)
    {
        $this->keyboardTemplate = $template;

        return $this;
    }

    /**
     * Render the paginator using the given template.
     *
     * @param  string|null  $template
     * @param  array  $data
     * @return \LaraGram\Contracts\Template\Template
     */
    public function render($template = null, $data = [])
    {
        return static::templateFactory()->make($template ?: static::$defaultTelegramTemplate, array_merge($data, [
            'paginator' => $this,
        ]));
    }

    /**
     * Render the paginator using the given template.
     *
     * @param  string|null  $template
     * @param  array  $data
     * @return \LaraGram\Contracts\Template\Template
     */
    public function links($template = null, $data = [])
    {
        return $this->render($template, $data);
    }

    /**
     * Get the template factory instance from the resolver.
     *
     * @return \LaraGram\Template\Factory
     */
    public static function templateFactory()
    {
        if (isset(static::$templateFactoryResolver)) {
            return call_user_func(static::$templateFactoryResolver);
        }

        return app('template');
    }

    /**
     * Set the template factory resolver callback.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function templateFactoryResolver(Closure $resolver)
    {
        static::$templateFactoryResolver = $resolver;
    }

    /**
     * Set the Telegram current page resolver callback.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function telegramCurrentPageResolver(Closure $resolver)
    {
        static::$telegramCurrentPageResolver = $resolver;
    }

    /**
     * Resolve the current page from the incoming callback query.
     *
     * @param  string  $pageName
     * @param  int  $default
     * @return int
     */
    public static function resolveCurrentPage($pageName = 'page', $default = 1)
    {
        if (isset(static::$telegramCurrentPageResolver)) {
            return (int) call_user_func(static::$telegramCurrentPageResolver, $pageName);
        }

        return $default;
    }
}
