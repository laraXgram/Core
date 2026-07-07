<?php

namespace LaraGram\Conversation;

use Closure;

/**
 * A single question within a conversation.
 */
class Question
{
    /**
     * The prompt text shown to the user.
     *
     * @var string
     */
    protected $prompt;

    /**
     * The key the answer is stored under.
     *
     * @var string
     */
    protected $name;

    /**
     * The expected answer content type (text, contact, photo, keyboard, ...).
     *
     * @var string
     */
    protected $type = 'text';

    /**
     * The validation rules applied to the extracted answer value.
     *
     * @var string|array|null
     */
    protected $rules = null;

    /**
     * Custom validation messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The command/text that skips this question, if any.
     *
     * @var string|null
     */
    protected $skipCommand = null;

    /**
     * The reply markup (keyboard) sent with the prompt.
     *
     * @var mixed
     */
    protected $keyboard = null;

    /**
     * The Telegram parse mode for the prompt.
     *
     * @var string|null
     */
    protected $parseMode = null;

    /**
     * A callback executed when this question is answered.
     *
     * @var \Closure|null
     */
    protected $callback = null;

    /**
     * Whether the answer callback runs at completion instead of immediately.
     *
     * @var bool
     */
    protected $deferred = false;

    /**
     * A custom sender for delivering the prompt (overrides default sendMessage).
     *
     * @var \Closure|null
     */
    protected $sender = null;

    /**
     * The maximum attempts allowed for this question (null = use conversation default).
     *
     * @var int|null
     */
    protected $maxAttempts = null;

    /**
     * The prompt delivery kind: text, photo, video, audio, voice, document,
     * animation, video_note or sticker.
     *
     * @var string
     */
    protected $promptKind = 'text';

    /**
     * The media to send as the prompt (file_id, URL, or InputFile). The prompt
     * text is used as the media caption where the type supports one.
     *
     * @var mixed
     */
    protected $promptMedia = null;

    /**
     * Per-question back override (null = inherit).
     *
     * @var \LaraGram\Conversation\Back|null
     */
    protected ?Back $back = null;

    /**
     * Per-question priority override (null = inherit).
     *
     * @var \LaraGram\Conversation\Priority|null
     */
    protected ?Priority $priority = null;

    /**
     * Create a new question.
     *
     * @param  string  $prompt
     * @return void
     */
    public function __construct(string $prompt)
    {
        $this->prompt = $prompt;
    }

    /**
     * Set the answer key.
     *
     * @param  string  $name
     * @return $this
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the expected answer content type.
     *
     * @param  string  $type
     * @return $this
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the validation rules for the answer.
     *
     * @param  string|array  $rules
     * @param  array  $messages
     * @return $this
     */
    public function validate(string|array $rules, array $messages = []): static
    {
        $this->rules = $rules;
        $this->messages = $messages;

        return $this;
    }

    /**
     * Set the command/text that skips this question.
     *
     * @param  string  $command
     * @return $this
     */
    public function skipCommand(string $command): static
    {
        $this->skipCommand = $command;

        return $this;
    }

    /**
     * Configure the back control for this question (overrides the global one).
     *
     * @param  string|null  $mode          reply | inline | command | text | none
     * @param  string|null  $label         Button text / matched text.
     * @param  string|null  $callbackData  Callback data for inline mode.
     * @param  string|null  $command       Command that triggers back.
     * @return $this
     */
    public function back(
        ?string $mode = null,
        ?string $label = null,
        ?string $callbackData = null,
        ?string $command = null,
    ): static {
        $this->back = Back::make($mode, $label, $callbackData, $command, enabled: true);

        return $this;
    }

    /**
     * Disable the back control for this question.
     *
     * @return $this
     */
    public function noBack(): static
    {
        $this->back = Back::disabled();

        return $this;
    }

    /**
     * Set who handles the update on this question: regular/step listens first
     * (Priority::Listen, the default) or this question first (Priority::Conversation).
     *
     * @param  \LaraGram\Conversation\Priority  $priority
     * @return $this
     */
    public function priority(Priority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Attach a reply markup (keyboard) to the prompt.
     *
     * @param  mixed  $keyboard
     * @return $this
     */
    public function keyboard($keyboard): static
    {
        $this->keyboard = $keyboard;

        return $this;
    }

    /**
     * Set the prompt parse mode.
     *
     * @param  string  $mode
     * @return $this
     */
    public function parseMode(string $mode): static
    {
        $this->parseMode = $mode;

        return $this;
    }

    /**
     * Register a callback to run after the question is answered.
     *
     * @param  \Closure  $callback
     * @param  bool  $defer
     * @return $this
     */
    public function then(Closure $callback, bool $defer = false): static
    {
        $this->callback = $callback;
        $this->deferred = $defer;

        return $this;
    }

    /**
     * Defer this question's answer callback to the end of the conversation.
     *
     * @return $this
     */
    public function defer(): static
    {
        $this->deferred = true;

        return $this;
    }

    /**
     * Deliver the prompt with a custom sender closure.
     *
     * @param  \Closure  $sender
     * @return $this
     */
    public function askUsing(Closure $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Override the maximum attempts allowed for this question.
     *
     * @param  int  $attempts
     * @return $this
     */
    public function attempts(int $attempts): static
    {
        $this->maxAttempts = $attempts;

        return $this;
    }

    /**
     * Send the prompt as a media message of the given kind.
     *
     * @param  string  $kind
     * @param  mixed  $file
     * @return $this
     */
    public function media(string $kind, $file): static
    {
        $this->promptKind = $kind;
        $this->promptMedia = $file;

        return $this;
    }

    /**
     * Send the prompt as a photo (prompt text becomes the caption).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function photo($file): static
    {
        return $this->media('photo', $file);
    }

    /**
     * Send the prompt as a video (prompt text becomes the caption).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function video($file): static
    {
        return $this->media('video', $file);
    }

    /**
     * Send the prompt as an audio file (prompt text becomes the caption).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function audio($file): static
    {
        return $this->media('audio', $file);
    }

    /**
     * Send the prompt as a voice message (prompt text becomes the caption).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function voice($file): static
    {
        return $this->media('voice', $file);
    }

    /**
     * Send the prompt as a document (prompt text becomes the caption).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function document($file): static
    {
        return $this->media('document', $file);
    }

    /**
     * Send the prompt as an animation/GIF (prompt text becomes the caption).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function animation($file): static
    {
        return $this->media('animation', $file);
    }

    /**
     * Send the prompt as a video note (caption not supported).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function videoNote($file): static
    {
        return $this->media('video_note', $file);
    }

    /**
     * Send the prompt as a sticker (caption not supported).
     *
     * @param  mixed  $file
     * @return $this
     */
    public function sticker($file): static
    {
        return $this->media('sticker', $file);
    }
}
