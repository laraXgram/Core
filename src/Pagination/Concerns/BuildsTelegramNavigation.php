<?php

namespace LaraGram\Pagination\Concerns;

use Closure;
use InvalidArgumentException;
use LaraGram\Pagination\UrlWindow;

trait BuildsTelegramNavigation
{
    /**
     * The prefix every navigation callback_data value starts with.
     *
     * @var string
     */
    public const PREFIX = 'paginate';

    /**
     * The maximum size of a Telegram callback_data value, in bytes.
     *
     * @var int
     */
    public const CALLBACK_DATA_LIMIT = 64;

    /**
     * The current page resolver callback (isolated from the web paginators).
     *
     * @var \Closure|null
     */
    protected static $telegramCurrentPageResolver;

    /**
     * The resolver returning the callback query of the current update.
     *
     * @var \Closure|null
     */
    protected static $telegramCallbackResolver;

    /**
     * The template factory resolver callback.
     *
     * @var \Closure|null
     */
    protected static $templateFactoryResolver;

    /**
     * The template compiler resolver callback.
     *
     * @var \Closure|null
     */
    protected static $templateCompilerResolver;

    /**
     * The prefix used for every navigation callback_data value.
     *
     * @var string
     */
    protected $callbackPrefix = self::PREFIX;

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
     * The heading shown above the results by the default template.
     *
     * @var string|null
     */
    protected $heading;

    /**
     * The callback turning an item into a line of the message.
     *
     * @var \Closure|null
     */
    protected $formatter;

    /**
     * The method used to send the first page.
     *
     * @var string
     */
    protected $sendMethod = 'sendMessage';

    /**
     * The method used when the reader navigates to another page.
     *
     * @var string
     */
    protected $editMethod = 'editMessageText';

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
     * current-page resolver and the onPaginate listen match against. Keep it
     * short (callback_data is limited to 64 bytes) and unique per screen.
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
     * Get the listen pattern matching the navigation of the given key.
     *
     * @param  string  $key
     * @return string
     */
    public static function listenPattern($key = 'page')
    {
        return static::PREFIX.':'.$key.':{page}';
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
     * The format of the page indicator button, false when it is disabled.
     *
     * @var string|false|null
     */
    protected $indicator;

    /**
     * Whether the keyboard is forced right-to-left, left-to-right, or neither.
     *
     * @var bool|null
     */
    protected $rightToLeft;

    /**
     * Get / set the format of the page indicator button.
     *
     * The format may use the {current}, {last}, {total}, {from}, {to} and
     * {perPage} placeholders. A simple paginator shows "{current}" unless it is
     * told otherwise; a numbered one marks the current page instead, and only
     * shows an indicator when one is set here.
     *
     * @param  string|null  $format
     * @return ($format is null ? string|false|null : $this)
     */
    public function indicator($format = null)
    {
        if (is_null($format)) {
            return $this->indicator;
        }

        $this->indicator = $format;

        return $this;
    }

    /**
     * Remove the page indicator button from the keyboard.
     *
     * @return $this
     */
    public function withoutIndicator()
    {
        $this->indicator = false;

        return $this;
    }

    /**
     * Get the text of the page indicator button, if it has one.
     *
     * @return string|null
     */
    public function resolvedIndicator()
    {
        $format = $this->indicator ?? ($this->hasKnownLastPage() ? null : '{current}');

        if ($format === false || is_null($format)) {
            return null;
        }

        $values = ['{current}' => $this->currentPage(), '{perPage}' => $this->perPage()];

        foreach (['last' => 'lastPage', 'total' => 'total', 'from' => 'firstItem', 'to' => 'lastItem'] as $name => $method) {
            $values['{'.$name.'}'] = method_exists($this, $method) ? $this->{$method}() : '';
        }

        return strtr($format, $values);
    }

    /**
     * Force the navigation keyboard to read right-to-left.
     *
     * Without this, the keyboard follows the locale of the application, like
     * every other keyboard built by LaraGram.
     *
     * @param  bool  $rightToLeft
     * @return $this
     */
    public function rightToLeft($rightToLeft = true)
    {
        $this->rightToLeft = $rightToLeft;

        return $this;
    }

    /**
     * Force the navigation keyboard to read left-to-right.
     *
     * @return $this
     */
    public function leftToRight()
    {
        return $this->rightToLeft(false);
    }

    /**
     * Get the forced direction of the keyboard, if any.
     *
     * @return bool|null
     */
    public function direction()
    {
        return $this->rightToLeft;
    }

    /**
     * Get / set the heading shown above the results.
     *
     * @param  string|null  $heading
     * @return ($heading is null ? string|null : $this)
     */
    public function heading($heading = null)
    {
        if (is_null($heading)) {
            return $this->heading;
        }

        $this->heading = $heading;

        return $this;
    }

    /**
     * Set the callback turning an item into a line of the message.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function formatUsing(Closure $callback)
    {
        $this->formatter = $callback;

        return $this;
    }

    /**
     * Format a single item for the message.
     *
     * @param  mixed  $item
     * @param  int|string|null  $key
     * @return string
     */
    public function format($item, $key = null)
    {
        if ($this->formatter) {
            return (string) call_user_func($this->formatter, $item, $key);
        }

        if (is_scalar($item) || $item instanceof \Stringable) {
            return (string) $item;
        }

        return (string) ($item->title ?? $item->name ?? $item->id ?? json_encode($item));
    }

    /**
     * Set the methods used to send the first page and to move between pages.
     *
     * @param  string|null  $send
     * @param  string|null  $edit
     * @return $this
     */
    public function methods($send = null, $edit = null)
    {
        if (! is_null($send)) {
            $this->sendMethod = $send;
        }

        if (! is_null($edit)) {
            $this->editMethod = $edit;
        }

        return $this;
    }

    /**
     * Get the Bot API method the paginated message should be sent with.
     *
     * The first page is sent, and every page after it edits the message the
     * button was tapped on, so the screen stays in place.
     *
     * @return string
     */
    public function method()
    {
        return $this->isNavigating() ? $this->editMethod : $this->sendMethod;
    }

    /**
     * Determine if the current update is a tap on this paginator's keyboard.
     *
     * @return bool
     */
    public function isNavigating()
    {
        $data = static::callbackQuery()['data'] ?? null;

        return is_string($data) && preg_match(
            '/^'.preg_quote($this->callbackPrefix, '/').':'.preg_quote($this->pageName, '/').':\d+$/', $data
        ) === 1;
    }

    /**
     * Get the identifier of the message being paginated, if any.
     *
     * @return int|null
     */
    public function messageId()
    {
        if (! $this->isNavigating()) {
            return null;
        }

        $messageId = static::callbackQuery()['message_id'] ?? null;

        return is_null($messageId) ? null : (int) $messageId;
    }

    /**
     * Build the callback_data value for the given page number.
     *
     * @param  int  $page
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function callbackData($page)
    {
        $data = $this->callbackPrefix.':'.$this->pageName.':'.$page;

        if (strlen($data) > static::CALLBACK_DATA_LIMIT) {
            throw new InvalidArgumentException(
                "The pagination key [{$this->pageName}] is too long: Telegram limits callback data to "
                .static::CALLBACK_DATA_LIMIT.' bytes.'
            );
        }

        return $data;
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

        $window = UrlWindow::make($this);

        return array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }

    /**
     * Get the page numbers shown on the keyboard, in order.
     *
     * Separators are dropped: a keyboard has no room for them, and the first
     * and last page are always within reach through their own buttons.
     *
     * @return array<int, int>
     */
    public function pages()
    {
        $pages = [];

        foreach ($this->elements() as $element) {
            if (is_array($element)) {
                $pages = array_merge($pages, array_keys($element));
            }
        }

        sort($pages);

        return array_values(array_unique($pages));
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
     * Get the resolved "next" button label.
     *
     * @return string
     */
    public function resolvedNextText()
    {
        return $this->nextText ?? $this->translate('pagination.next', 'Next »');
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
     * Determine if the paginator knows its last page.
     *
     * @return bool
     */
    public function hasKnownLastPage()
    {
        return method_exists($this, 'lastPage');
    }

    /**
     * Render the navigation keyboard from the keyboard template.
     *
     * The layout lives in a publishable template (default: the core
     * "pagination::keyboard"), which is a plain Temple8 @keyboard block. The
     * template is compiled and cached like any other, then evaluated on its own
     * so the reply_markup it builds can be captured instead of sent.
     *
     * @return string  The reply_markup JSON.
     */
    public function keyboard()
    {
        $template = static::templateFactory()->make($this->keyboardTemplate ?? ($this->hasKnownLastPage()
            ? static::$defaultKeyboardTemplate
            : static::$defaultSimpleKeyboardTemplate), [
                'paginator' => $this,
                'elements' => $this->elements(),
            ]);

        return trim((string) static::evaluateKeyboard(
            static::compiledKeyboardPath($template->getPath()), $template->gatherData()
        ));
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
     * Compile the keyboard template if needed and get its compiled path.
     *
     * @param  string  $path
     * @return string
     */
    protected static function compiledKeyboardPath($path)
    {
        $compiler = static::templateCompiler();

        if ($compiler->isExpired($path)) {
            $compiler->compile($path);
        }

        return $compiler->getCompiledPath($path);
    }

    /**
     * Evaluate a compiled keyboard template and capture its reply_markup.
     *
     * @param  string  $__path
     * @param  array  $__data
     * @return string
     */
    protected static function evaluateKeyboard($__path, array $__data)
    {
        return (static function () use ($__path, $__data) {
            extract($__data);

            ob_start();

            try {
                include $__path;
            } finally {
                ob_end_clean();
            }

            return $__t8__reply_markup ?? '';
        })();
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
     * Get the template compiler instance from the resolver.
     *
     * @return \LaraGram\Template\Compilers\Temple8Compiler
     */
    public static function templateCompiler()
    {
        if (isset(static::$templateCompilerResolver)) {
            return call_user_func(static::$templateCompilerResolver);
        }

        return app('temple8.compiler');
    }

    /**
     * Set the template compiler resolver callback.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function templateCompilerResolver(Closure $resolver)
    {
        static::$templateCompilerResolver = $resolver;
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
     * Set the resolver returning the callback query of the current update.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function telegramCallbackResolver(Closure $resolver)
    {
        static::$telegramCallbackResolver = $resolver;
    }

    /**
     * Get the callback query of the current update, if any.
     *
     * @return array{data?: string|null, message_id?: int|null}
     */
    protected static function callbackQuery()
    {
        if (! isset(static::$telegramCallbackResolver)) {
            return [];
        }

        return (array) call_user_func(static::$telegramCallbackResolver);
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
