<?php

namespace LaraGram\Conversation;

use Closure;

/**
 * A single question within a conversation.
 */
class Question
{
    /**
     * The prompt shown to the user, or a closure building it from the answers.
     *
     * @var string|\Closure|null
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
     * The options offered by the question, if any.
     *
     * @var \LaraGram\Conversation\Choices|null
     */
    protected ?Choices $choices = null;

    /**
     * The template rendering the prompt, if any.
     *
     * @var array{template: string, data: array, source: string}|null
     */
    protected ?array $template = null;

    /**
     * The condition deciding whether the question is asked.
     *
     * @var \Closure|null
     */
    protected $condition = null;

    /**
     * The callback converting the answer before it is stored.
     *
     * @var \Closure|null
     */
    protected $transform = null;

    /**
     * The value stored when the question is skipped.
     *
     * @var mixed
     */
    protected $default = null;

    /**
     * The label of the button that skips the question, if any.
     *
     * @var string|null
     */
    protected $skipLabel = null;

    /**
     * The message sent when an answer is rejected.
     *
     * @var string|\Closure|null
     */
    protected $retry = null;

    /**
     * Create a new question.
     *
     * @param  string|\Closure|null  $prompt
     * @return void
     */
    public function __construct(string|Closure|null $prompt = null)
    {
        $this->prompt = $prompt;
    }

    /**
     * Set the prompt, or a closure building it from the answers so far.
     *
     * @param  string|\Closure  $prompt
     * @return $this
     */
    public function prompt(string|Closure $prompt): static
    {
        $this->prompt = $prompt;

        return $this;
    }

    /**
     * Offer a fixed set of options, presented as a keyboard.
     *
     * The array maps the value that is stored to the label the user sees; a
     * plain list uses each item as both. A closure receives the answers given
     * so far and returns that array.
     *
     * @param  array<int|string, string>|\Closure  $options
     * @param  int|null  $columns
     * @return $this
     */
    public function choices(array|Closure $options, ?int $columns = null): static
    {
        $this->choices = new Choices($options, columns: $columns ?? 2);

        if ($this->type === 'text') {
            $this->type = 'any';
        }

        return $this;
    }

    /**
     * Present the options as an inline keyboard (the default).
     *
     * @return $this
     */
    public function asInline(): static
    {
        $this->choices = $this->requireChoices(__FUNCTION__)->with(['layout' => 'inline']);

        return $this;
    }

    /**
     * Present the options as a reply keyboard.
     *
     * @return $this
     */
    public function asReply(): static
    {
        $this->choices = $this->requireChoices(__FUNCTION__)->with(['layout' => 'reply']);

        return $this;
    }

    /**
     * Set how many option buttons sit in a keyboard row.
     *
     * @param  int  $columns
     * @return $this
     */
    public function columns(int $columns): static
    {
        $this->choices = $this->requireChoices(__FUNCTION__)->with(['columns' => $columns]);

        return $this;
    }

    /**
     * Let the user select several options, until the "done" button is tapped.
     *
     * The answer is an array of the selected values.
     *
     * @param  string  $done
     * @param  int|null  $min
     * @param  int|null  $max
     * @return $this
     */
    public function multiple(string $done = 'Done', ?int $min = null, ?int $max = null): static
    {
        $this->choices = $this->requireChoices(__FUNCTION__)->with([
            'multiple' => true, 'done' => $done, 'min' => $min, 'max' => $max,
        ]);

        return $this;
    }

    /**
     * Ask for a yes or no, storing the answer as a boolean.
     *
     * @param  string  $yes
     * @param  string  $no
     * @return $this
     */
    public function confirm(string $yes = 'Yes', string $no = 'No'): static
    {
        return $this->choices(['1' => $yes, '0' => $no], columns: 2)
            ->transform(fn ($value) => (bool) $value);
    }

    /**
     * Render the prompt with a template.
     *
     * The template builds the whole message - text, parse mode, keyboard,
     * media, rich message - and the back or skip buttons are added to the
     * keyboard it produces.
     *
     * @param  string  $template
     * @param  array<string, mixed>  $data
     * @param  string  $source  name | path | inline
     * @return $this
     */
    public function template(string $template, array $data = [], string $source = 'name'): static
    {
        $this->template = ['template' => $template, 'data' => $data, 'source' => $source];

        return $this;
    }

    /**
     * Ask the question only when the given condition passes.
     *
     * The closure receives the answers given so far.
     *
     * @param  \Closure  $condition
     * @return $this
     */
    public function when(Closure $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Ask the question unless the given condition passes.
     *
     * @param  \Closure  $condition
     * @return $this
     */
    public function unless(Closure $condition): static
    {
        $this->condition = static fn (AnswersBag $answers) => ! $condition($answers);

        return $this;
    }

    /**
     * Convert the answer before it is stored.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function transform(Closure $callback): static
    {
        $this->transform = $callback;

        return $this;
    }

    /**
     * Cast the answer to the given type before it is stored.
     *
     * @param  string  $type  int | float | bool | string | array
     * @return $this
     */
    public function cast(string $type): static
    {
        return $this->transform(static fn ($value) => match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'string' => (string) $value,
            'array' => (array) $value,
            default => $value,
        });
    }

    /**
     * Set the value stored when the question is skipped.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    /**
     * Let the question be skipped with a button, storing the default value.
     *
     * @param  string  $label
     * @param  mixed  $default
     * @return $this
     */
    public function optional(string $label = 'Skip', mixed $default = null): static
    {
        $this->skipLabel = $label;
        $this->default = $default;

        return $this;
    }

    /**
     * Set the message sent when an answer is rejected.
     *
     * The closure receives the validation errors and the attempt number.
     *
     * @param  string|\Closure  $message
     * @return $this
     */
    public function retry(string|Closure $message): static
    {
        $this->retry = $message;

        return $this;
    }

    /**
     * Get the question's options, failing when it has none.
     *
     * @param  string  $method
     * @return \LaraGram\Conversation\Choices
     *
     * @throws \LogicException
     */
    protected function requireChoices(string $method): Choices
    {
        return $this->choices ?? throw new \LogicException(
            "The [{$method}] method may only be used after choices() has been called."
        );
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
