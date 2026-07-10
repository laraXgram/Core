<?php

namespace LaraGram\Request;

use Closure;
use LaraGram\Laraquest\Updates as UpdatesTrait;
use LaraGram\Laraquest\Methode as MethodeTrait;
use LaraGram\Listening\Contracts\ProvidesListenContext;
use LaraGram\Listening\Type;
use LaraGram\Request\Files\FileBag;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\Conditionable;
use LaraGram\Support\Traits\Macroable;
use RuntimeException;

/**
 * @method \LaraGram\Request\ValidatedInput validate(array $rules, ...$params)
 * @method \LaraGram\Request\ValidatedInput validateWithBag(string $errorBag, array $rules, ...$params)
 */
class Request implements ProvidesListenContext
{
    use Conditionable, Macroable,
        InteractWithUpdate,
        MethodeTrait, UpdatesTrait {
        InteractWithUpdate::getUpdateType insteadof UpdatesTrait;
        InteractWithUpdate::getUpdateMessageSubType insteadof UpdatesTrait;
        InteractWithUpdate::scope insteadof UpdatesTrait;
        InteractWithUpdate::isReply insteadof UpdatesTrait;
        MethodeTrait::endpoint as protected rawEndpoint;
    }

    /**
     * Whether anti-flood throttling is bypassed for the next API call.
     *
     * @var bool
     */
    protected $bypassAntiFlood = false;

    /**
     * Specific named anti-flood scope(s).
     *
     * @var string|null
     */
    protected $antiFloodScopes = null;

    /**
     * The user resolver callback.
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * The listen resolver callback.
     *
     * @var \Closure
     */
    protected $listenResolver;

    /**
     * The incoming request.
     *
     * @var array
     */
    protected $request;

    /**
     * The request contents.
     *
     * @var UpdatesTrait|Collection
     */
    protected $content;

    /**
     * The server data.
     *
     * @var Collection
     */
    protected $server;

    protected ?string $locale = null;
    protected string $defaultLocale = 'en';

    /**
     * Cached FileBag instance for the current update.
     *
     * @var FileBag|null
     */
    private ?FileBag $fileBag = null;

    public function __construct(array $request = [], array $server = [])
    {
        $this->request = collect($request);
        $this->server = collect($server);
    }

    /**
     * Creates a Request based on a given configuration.
     *
     *
     * @param array $server The server parameters ($_SERVER)
     * @param array $content The raw body data
     *
     */
    public static function create(array $content = [], array $server = []): static
    {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'LaraGram',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,application/json;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $server);

        $server['PATH_INFO'] = '';

        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':' . $components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        return new static($content, $server);
    }

    /**
     * Get the server from the request.
     *
     * @return Collection
     */
    public function server()
    {
        return $this->server;
    }

    /**
     * Get the secret token from the request headers.
     *
     * @return string|null
     */
    public function secretToken()
    {
        return $this->server->get('HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN', '');
    }

    /**
     * Create a new LaraGram HTTP request from server variables.
     *
     * @return static
     */
    public static function capture()
    {
        global $argv;
        return static::createFromBase($argv ?? []);
    }

    /**
     * Return the Request instance.
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Get the full update for the request.
     *
     * @return UpdatesTrait
     */
    public function update()
    {
        return $this->getInputSource();
    }

    /**
     * Get the match method.
     *
     * @return string
     */
    public function method()
    {
        if (($method = $this->checkIfMethodIsCommand())) {
            return $method;
        }

        if (($method = $this->checkIfMethodIsCallbackQuery())) {
            return $method;
        }

        if (($method = $this->checkIfMethodIsPollUpdate())) {
            return $method;
        }

        return strtoupper(Type::findVerb($this->getUpdateType())?->value ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function listenVerb(): string
    {
        return $this->method();
    }

    /**
     * {@inheritdoc}
     */
    public function listenValue(string $verb): ?string
    {
        return match ($verb) {
            'COMMAND' => ($t = text()) !== null ? Str::replaceFirst('/', '', $t) : null,
            'REFERRAL' => ($t = text()) !== null ? Str::replaceFirst('/start ', '', $t) : null,
            'CALLBACK_DATA' => callback_query()->data ?? null,
            default => text()
                ?? callback_query()->data
                ?? inline_query()->query
                ?? chosen_inline_result()->query
                ?? null,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function listenVerb(): string
    {
        return $this->method();
    }

    /**
     * {@inheritdoc}
     */
    public function listenValue(string $verb): ?string
    {
        return match ($verb) {
            'COMMAND' => ($t = text()) !== null ? Str::replaceFirst('/', '', $t) : null,
            'REFERRAL' => ($t = text()) !== null ? Str::replaceFirst('/start ', '', $t) : null,
            'CALLBACK_DATA' => callback_query()->data ?? null,
            default => text()
                ?? callback_query()->data
                ?? inline_query()->query
                ?? chosen_inline_result()->query
                ?? null,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function entities(): array
    {
        return $this->message?->entities
            ?? $this->message?->caption_entities
            ?? [];
    }

    /**
     * Check if the Update method is a top-level poll state update.
     *
     * @return false|string
     */
    protected function checkIfMethodIsPollUpdate()
    {
        if (isset($this->poll)) {
            return 'UPDATE';
        }
        return false;
    }

    /**
     * Check if the Update method is COMMAND or REFERRAL
     *
     * @return false|string
     */
    protected function checkIfMethodIsCommand()
    {
        if (isset($this->message->entities) && $this->message->entities[0]->type === 'bot_command') {
            $command = str_replace('/start ', '', $this->message->text);
            return $command !== '' && $command !== $this->message->text
                ? 'REFERRAL'
                : 'COMMAND';
        }
        return false;
    }

    /**
     * Check if the Update method is CALLBACK_DATA
     *
     * @return false|string
     */
    protected function checkIfMethodIsCallbackQuery()
    {
        if (isset($this->callback_query->data)) {
            return 'CALLBACK_DATA';
        }
        return false;
    }

    /**
     * Get the match method.
     *
     * @param  string  $method
     * @return string
     */
    public function isMethod($method)
    {
        return $method == $this->method();
    }

    /**
     * Determine if the listen name matches a given pattern.
     *
     * @param mixed ...$patterns
     * @return bool
     */
    public function listenIs(...$patterns)
    {
        return $this->listen() && $this->listen()->named(...$patterns);
    }

    /**
     * Merge new input into the current request's input array.
     *
     * @param array $input
     * @return $this
     */
    public function merge(array $input)
    {
        $this->getInputSource()->add($input);

        return $this;
    }

    /**
     * Merge new input into the request's input, but only when that key is missing from the request.
     *
     * @param array $input
     * @return $this
     */
    public function mergeIfMissing(array $input)
    {
        return $this->merge((new Collection($input))
            ->filter(fn($value, $key) => $this->missing($key))
            ->toArray()
        );
    }

    /**
     * Replace the input values for the current request.
     *
     * @param array $input
     * @return $this
     */
    public function replace(array $input)
    {
        $this->getInputSource()->replace($input);

        return $this;
    }

    /**
     * Get the input source for the request.
     *
     * @return Collection
     */
    protected function getInputSource()
    {
        return $this->content;
    }

    /**
     * Get the entire update as a nested associative array.
     *
     * @param  array|string|null  $keys
     * @return array
     */
    public function all($keys = null)
    {
        $input = $this->toArray();

        if (! $keys) {
            return $input;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            data_set($results, $key, data_get($input, $key));
        }

        return $results;
    }

    /**
     * Retrieve an input item from the update using "dot" notation.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->all();
        }

        return data_get($this->toArray(), $key, $default);
    }

    /**
     * Determine if the update contains a given input item key.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->toArray();

        foreach ($keys as $value) {
            if (! data_get($input, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the update is missing a given input item key.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function missing($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        return ! $this->has($keys);
    }

    /**
     * Get a subset containing the provided keys from the update.
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = [];
        $input = $this->toArray();

        foreach ($keys as $key) {
            data_set($results, $key, data_get($input, $key));
        }

        return $results;
    }

    /**
     * Get all of the update input except for a specified array of items.
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = $this->toArray();

        foreach ($keys as $key) {
            data_set($results, $key, null);
        }

        return $results;
    }

    /**
     * Normalize the current update to a nested associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $source = $this->getInputSource();

        if ($source === null) {
            return [];
        }

        return json_decode(json_encode($source), true) ?? [];
    }

    /**
     * Create an LaraGram request from a Symfony instance.
     *
     * @param array $request
     * @return static
     */
    public static function createFromBase(array $request)
    {
        $newRequest = new static($request);

        $newRequest->server = collect(json_decode($request[2] ?? "{}"));

        $newRequest->content = collect(json_decode($request[1] ?? "{}"));

        return $newRequest;
    }

    /**
     * Set the locale for the request instance.
     *
     * @param string $locale
     * @return void
     */
    public function setRequestLocale(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * Set the default locale for the request instance.
     *
     * @param string $locale
     * @return void
     */
    public function setDefaultRequestLocale(string $locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * Get the user making the request.
     *
     * @return mixed
     */
    public function user()
    {
        return call_user_func($this->getUserResolver());
    }

    /**
     * Get the listen handling the request.
     *
     * @param string|null $param
     * @param mixed $default
     * @return \LaraGram\Listening\Listen|object|string|null
     */
    public function listen($param = null, $default = null)
    {
        $listen = call_user_func($this->getListenResolver());

        if (is_null($listen) || is_null($param)) {
            return $listen;
        }

        return $listen->parameter($param, $default);
    }

    /**
     * Get a unique fingerprint for the request / listen / IP address.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function fingerprint()
    {
        if (!$listen = $this->listen()) {
            throw new RuntimeException('Unable to generate fingerprint. Listen unavailable.');
        }

        return sha1(implode('|', array_merge(
            $listen->methods(),
            [$listen->pattern(), user()->id]
        )));
    }

    /**
     * Determine whether the current update contains any media file.
     *
     * Checks for the presence of photo, video, document, sticker, audio,
     * voice, video_note, animation, or live_photo in the message.
     *
     * @return bool
     */
    public function hasFile(): bool
    {
        $message = $this->message ?? null;

        if ($message === null) {
            return false;
        }

        return isset($message->photo)
            || isset($message->video)
            || isset($message->document)
            || isset($message->sticker)
            || isset($message->audio)
            || isset($message->voice)
            || isset($message->video_note)
            || isset($message->animation)
            || isset($message->live_photo)
            || isset($message->paid_media);
    }

    /**
     * Get the FileBag for the current update.
     *
     * @return FileBag|null
     */
    public function file(): ?FileBag
    {
        if ($this->fileBag !== null) {
            return $this->fileBag;
        }

        $message = $this->message ?? null;

        if ($message === null) {
            return null;
        }

        $cfg = $this->resolveConfig();
        $token = $this->resolveToken($this->resolveConnection());
        $apiServer = $cfg['api_server'];

        $bag = FileBag::fromMessage($message, $token, $apiServer);

        if ($bag->isEmpty()) {
            return null;
        }

        return $this->fileBag = $bag;
    }

    /**
     * Build a FileBag from an arbitrary message-like object.
     *
     * @param  object  $message
     * @return FileBag|null
     */
    public function fileFrom(object $message): ?FileBag
    {
        $cfg = $this->resolveConfig();
        $token = $this->resolveToken($this->resolveConnection());

        $bag = FileBag::fromMessage($message, $token, $cfg['api_server']);

        return $bag->isEmpty() ? null : $bag;
    }

    /**
     * Get the user resolver callback.
     *
     * @return \Closure
     */
    public function getUserResolver()
    {
        return $this->userResolver ?: function () {
            //
        };
    }

    /**
     * Set the user resolver callback.
     *
     * @param \Closure $callback
     * @return $this
     */
    public function setUserResolver(Closure $callback)
    {
        $this->userResolver = $callback;

        return $this;
    }

    /**
     * Get the listen resolver callback.
     *
     * @return \Closure
     */
    public function getListenResolver()
    {
        return $this->listenResolver ?: function () {
            //
        };
    }

    /**
     * Set the listen resolver callback.
     *
     * @param \Closure $callback
     * @return $this
     */
    public function setListenResolver(Closure $callback)
    {
        $this->listenResolver = $callback;

        return $this;
    }

    /**
     * Send the next API call without anti-flood throttling.
     *
     * @return $this
     */
    public function withoutAntiFlood(): static
    {
        $this->bypassAntiFlood = true;

        return $this;
    }

    /**
     * Apply specific named anti-flood scope(s) to the next API call instead of
     * the automatic chat scope (the global scope still applies).
     *
     * @param  string  ...$scopes
     * @return $this
     */
    public function antiFloodWith(string ...$scopes): static
    {
        $this->antiFloodScopes = $scopes;

        return $this;
    }

    /**
     * Intercept every Telegram API call to apply smart anti-flood throttling,
     * then delegate to the original Laraquest endpoint implementation.
     *
     * @param  string  $method
     * @param  array   $params
     * @return mixed
     */
    protected function endpoint(string $method, array $params): mixed
    {
        $bypass = $this->bypassAntiFlood;
        $this->bypassAntiFlood = false;

        $scopes = $this->antiFloodScopes ?? [];
        $this->antiFloodScopes = null;

        $antiFlood = $bypass ? null : $this->antiFlood();
        $connection = null;

        if ($antiFlood !== null) {
            try {
                $connection = $this->getConnection();
                $antiFlood->gate($connection, $method, $params, $scopes);
            } catch (\Throwable) {
                $antiFlood = null;
            }
        }

        $response = $this->rawEndpoint($method, $params);

        if ($antiFlood !== null) {
            try {
                $antiFlood->report($connection, $method, $params, $response, $scopes);
            } catch (\Throwable) {
                // Reporting must never affect the response returned to the caller.
            }
        }

        return $response;
    }

    /**
     * Resolve the active anti-flood engine, or null when it is unavailable.
     *
     * @return \LaraGram\Request\AntiFlood\AntiFlood|null
     */
    private function antiFlood()
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            $app = app();

            if (! $app->bound('antiflood')) {
                return null;
            }

            $engine = $app->make('antiflood');

            return $engine->enabled() ? $engine : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Check if an input element is set on the request.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key): bool
    {
        return !is_null($this->__get($key));
    }

    /**
     * Get an input element from the request.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this?->update()?->get($name);
    }
}
