<?php

namespace LaraGram\Request;

use Closure;
use LaraGram\Laraquest\Exceptions\InvalidUpdateType;
use LaraGram\Laraquest\Updates as UpdatesTrait;
use LaraGram\Laraquest\Methode as MethodeTrait;
use LaraGram\Listening\Type;
use LaraGram\Support\Collection;
use LaraGram\Support\Traits\Conditionable;
use LaraGram\Support\Traits\Macroable;
use RuntimeException;

/**
 * @mixin \LaraGram\Laraquest\Updates|\LaraGram\Laraquest\Methode
 *
 * @method array validate(array $rules, ...$params)
 * @method array validateWithBag(string $errorBag, array $rules, ...$params)
 * @method bool hasValidSignature(bool $absolute = true)
 */
class Request
{
    use Conditionable, Macroable,
        UpdatesTrait, MethodeTrait;

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

    public function __construct(array $request = [], array $server = [])
    {
        $this->request = $request;
        $this->server = $server;
    }

    /**
     * Creates a Request based on a given configuration.
     *
     *
     * @param array                $server     The server parameters ($_SERVER)
     * @param array                $content    The raw body data
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
            $server['HTTP_HOST'] .= ':'.$components['port'];
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
        return static::createFromBase($argv);
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
     * This function returns the type of the update.
     *
     * @return false|string
     * @throws InvalidUpdateType
     */
    public function getUpdateType(): false|string
    {
        return match (true) {
            isset($this->inline_query) => 'inline_query',
            isset($this->callback_query) => 'callback_query',
            isset($this->edited_message) => 'edited_message',
            isset($this->message) => $this->getUpdateMessageSubType($this->message),
            isset($this->my_chat_member) => 'my_chat_member',
            isset($this->channel_post) => 'channel_post',
            default => false
        };
    }

    /**
     * This function returns the type of the message.
     *
     * @param  object $message
     * @return string
     */
    public function getUpdateMessageSubType(object $message): string
    {
        return match (true) {
            isset($message->animation) => 'animation',
            isset($message->audio) => 'audio',
            isset($message->contact) => 'contact',
            isset($message->dice) => 'dice',
            isset($message->document) => 'document',
            isset($message->left_chat_member) => 'left_chat_member',
            isset($message->location) => 'location',
            isset($message->new_chat_members) => 'new_chat_members',
            isset($message->photo) => 'photo',
            isset($message->reply_to_message) => 'reply_to_message',
            isset($message->sticker) => 'sticker',
            isset($message->text) => 'text',
            isset($message->video) => 'video',
            isset($message->video_note) => 'video_note',
            isset($message->voice) => 'voice',
            default => throw new InvalidUpdateType('Unknown message type')
        };
    }

    /**
     * detect the scope of message.
     *
     * @return string
     */
    public function scope()
    {
        return match (true) {
            isset($this->message->chat->type) => $this->message->chat->type,
            isset($this->edited_message->chat->type) => $this->edited_message->chat->type,
            isset($this->channel_post->chat->type) => $this->channel_post->chat->type,
            isset($this->edited_channel_post->chat->type) => $this->edited_channel_post->chat->type,
            isset($this->business_message->chat->type) => $this->business_message->chat->type,
            isset($this->edited_business_message->chat->type) => $this->edited_business_message->chat->type,
            isset($this->deleted_business_messages->chat->type) => $this->deleted_business_messages->chat->type,
            isset($this->message_reaction->chat->type) => $this->message_reaction->chat->type,
            isset($this->message_reaction_count->chat->type) => $this->message_reaction_count->chat->type,
            isset($this->callback_query->message->chat->type) => $this->callback_query->message->chat->type,
            isset($this->poll_answer->voter_chat->type) => $this->poll_answer->voter_chat->type,
            isset($this->my_chat_member->chat->type) => $this->my_chat_member->chat->type,
            isset($this->chat_member->chat->type) => $this->chat_member->chat->type,
            isset($this->chat_join_request->chat->type) => $this->chat_join_request->chat->type,
            isset($this->chat_boost->chat->type) => $this->chat_boost->chat->type,
            isset($this->removed_chat_boost->chat->type) => $this->removed_chat_boost->chat->type,
            default => ''
        };
    }

    /**
     * detect the message is reply or not.
     *
     * @return bool
     */
    public function isReply()
    {
        return match (true) {
            isset($this->message->reply_to_message),
            isset($this->edited_message->reply_to_message),
            isset($this->channel_post->reply_to_message),
            isset($this->edited_channel_post->reply_to_message),
            isset($this->business_message->reply_to_message),
            isset($this->edited_business_message->reply_to_message),
            isset($this->callback_query->message->reply_to_message) => true,
            default => false
        };
    }

    /**
     * Get the full update for the request.
     *
     * @return UpdatesTrait
     */
    public function update()
    {
        return json_decode($this->content);
    }

    /**
     * Get the match method.
     *
     * @return string
     */
    public function method()
    {
        if (($method = $this->checkIfMethodIsCommand())){
            return $method;
        }

        if (($method = $this->checkIfMethodIsCallbackQuery())){
            return $method;
        }

        return strtoupper(Type::findVerb($this->getUpdateType())->value);
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
     * Create an LaraGram request from a Symfony instance.
     *
     * @param array $request
     * @return static
     */
    public static function createFromBase(array $request)
    {
        $newRequest = new static($request);

        $newRequest->server = collect($request[2]);

        $newRequest->content = collect($request[1]);

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
     * Check if an input element is set on the request.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
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
        return json_decode($this->update()[0])->{$name} ?? null;
    }
}
