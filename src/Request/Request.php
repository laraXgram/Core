<?php

namespace LaraGram\Request;

use Closure;
use LaraGram\Laraquest\Exceptions\InvalidUpdateType;
use LaraGram\Laraquest\Methode as MethodeTrait;
use LaraGram\Laraquest\Updates as UpdatesTrait;
use LaraGram\Listening\Type;
use LaraGram\Support\Collection;
use LaraGram\Support\Traits\Conditionable;
use LaraGram\Support\Traits\Macroable;
use RuntimeException;

/**
 * @method array validate(array $rules, ...$params)
 * @method array validateWithBag(string $errorBag, array $rules, ...$params)
 * @method bool hasValidSignature(bool $absolute = true)
 */
class Request
{
    use Concerns\InteractsWithServerInput,
        MethodeTrait, UpdatesTrait,
        Conditionable,
        Macroable;

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

    public function __construct(array $request = [])
    {
        $this->request = $request;
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
        /** @var UpdatesTrait $update */
        $update = json_decode($this->update()[0]);
        return match (true) {
            isset($update->inline_query) => 'inline_query',
            isset($update->callback_query) => 'callback_query',
            isset($update->edited_message) => 'edited_message',
            isset($update->message) => $this->getUpdateMessageSubType($update->message),
            isset($update->my_chat_member) => 'my_chat_member',
            isset($update->channel_post) => 'channel_post',
            default => false
        };
    }

    /**
     * This function returns the type of the message.
     *
     * @param  object|UpdatesTrait\Message  $message
     * @return string
     * @throws InvalidUpdateType
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
        /** @var UpdatesTrait $update */
        $update = json_decode($this->update()[0]);
        return match (true) {
            isset($update->message->chat->type) => $update->message->chat->type,
            isset($update->edited_message->chat->type) => $update->edited_message->chat->type,
            isset($update->channel_post->chat->type) => $update->channel_post->chat->type,
            isset($update->edited_channel_post->chat->type) => $update->edited_channel_post->chat->type,
            isset($update->business_message->chat->type) => $update->business_message->chat->type,
            isset($update->edited_business_message->chat->type) => $update->edited_business_message->chat->type,
            isset($update->deleted_business_messages->chat->type) => $update->deleted_business_messages->chat->type,
            isset($update->message_reaction->chat->type) => $update->message_reaction->chat->type,
            isset($update->message_reaction_count->chat->type) => $update->message_reaction_count->chat->type,
            isset($update->callback_query->message->chat->type) => $update->callback_query->message->chat->type,
            isset($update->poll_answer->voter_chat->type) => $update->poll_answer->voter_chat->type,
            isset($update->my_chat_member->chat->type) => $update->my_chat_member->chat->type,
            isset($update->chat_member->chat->type) => $update->chat_member->chat->type,
            isset($update->chat_join_request->chat->type) => $update->chat_join_request->chat->type,
            isset($update->chat_boost->chat->type) => $update->chat_boost->chat->type,
            isset($update->removed_chat_boost->chat->type) => $update->removed_chat_boost->chat->type,
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
        /** @var UpdatesTrait $update */
        $update = json_decode($this->update()[0]);
        return match (true) {
            isset($update->message->reply_to_message),
            isset($update->edited_message->reply_to_message),
            isset($update->channel_post->reply_to_message),
            isset($update->edited_channel_post->reply_to_message),
            isset($update->business_message->reply_to_message),
            isset($update->edited_business_message->reply_to_message),
            isset($update->callback_query->message->reply_to_message) => true,
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
        return $this->content;
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
    public function checkIfMethodIsCommand()
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
    public function checkIfMethodIsCallbackQuery()
    {
        if (isset($this->callback_query)) {
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
     * Get the HTTP host being requested.
     *
     * @return string
     */
    public function httpHost()
    {
        return $this->server('HTTP_HOST');
    }

    /**
     * Determine if the request is over HTTPS.
     *
     * @return bool
     */
    public function secure()
    {
        if (
            (!$this->serverHas('HTTPS') && $this->server('HTTPS') !== 'off') ||
            (!$this->serverHas('SERVER_PORT') && $this->server('SERVER_PORT') == 443) ||
            (!$this->serverHas('REQUEST_SCHEME') && $this->server('REQUEST_SCHEME') === 'https') ||
            (!$this->serverHas('HTTP_X_FORWARDED_PROTO') && $this->server('HTTP_X_FORWARDED_PROTO') === 'https')
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get the client IP address.
     *
     * @return string|null
     */
    public function ip()
    {
        return $this->server('REMOTE_ADDR');
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
     * @param string|null $guard
     * @return mixed
     */
    public function user($guard = null)
    {
        return call_user_func($this->getUserResolver(), $guard);
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
            [$listen->pattern(), id()]
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
        return json_decode($this->update()[0])->{$name} ?? fn() => $this->listen($name);
    }
}
