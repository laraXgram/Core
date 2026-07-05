<?php

namespace LaraGram\Conversation;

use Closure;
use LaraGram\Cache\Repository as CacheRepository;
use LaraGram\Contracts\Config\Repository as Config;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Conversation\Events\AnswerInvalid;
use LaraGram\Conversation\Events\AnswerReceived;
use LaraGram\Conversation\Events\ConversationCancelled;
use LaraGram\Conversation\Events\ConversationCompleted;
use LaraGram\Conversation\Events\ConversationStarted;
use LaraGram\Conversation\Events\QuestionAsked;
use LaraGram\Conversation\Events\QuestionSkipped;
use LaraGram\Request\Request;
use LaraGram\Support\Tempora;
use LaraGram\Validation\Factory as ValidationFactory;
use RuntimeException;

class ConversationManager
{
    /**
     * The application container.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $container;

    /**
     * The configuration repository.
     *
     * @var \LaraGram\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The event dispatcher.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The validation factory.
     *
     * @var \LaraGram\Validation\Factory
     */
    protected $validator;

    /**
     * The conversation currently being defined (for the create() facade form).
     *
     * @var \LaraGram\Conversation\Conversation|null
     */
    protected $defining = null;

    /**
     * Create a new conversation manager.
     *
     * @return void
     */
    public function __construct(
        Container $container,
        Config $config,
        Dispatcher $events,
        ValidationFactory $validator
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->events = $events;
        $this->validator = $validator;
    }

    /**
     * Start a conversation for the current user.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $parameters
     * @return void
     */
    public function start(string $name, array $parameters = []): void
    {
        $conversation = $this->resolve($name);

        $questions = $this->buildQuestions($conversation);

        $now = Tempora::now()->getTimestamp();

        $state = [
            'name'       => $name,
            'index'      => 0,
            'answers'    => [],
            'attempts'   => 0,
            'parameters' => $parameters,
            'started_at' => $now,
            'updated_at' => $now,
        ];

        $this->putState($state);

        $request = $this->request();

        $conversation->onStart($request);
        $this->events->dispatch(new ConversationStarted($name, $conversation));

        if ($first = $questions->get(0)) {
            $this->askQuestion($conversation, $first, $name);
        } else {
            // A conversation with no questions completes immediately.
            $this->complete($conversation, $request, $questions, $state);
        }
    }

    /**
     * Declare questions through the facade form (Conversation::create(...)).
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function create(Closure $callback): void
    {
        if ($this->defining === null) {
            throw new RuntimeException(
                'Conversation::create() may only be called while a conversation is being defined.'
            );
        }

        $this->defining->create($callback);
    }

    /**
     * Handle an incoming update against the active conversation, if any.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return bool  True if the update was consumed by a conversation.
     */
    public function handle(Request $request): bool
    {
        // Updates without a user (e.g. polls) can't own conversation state.
        if (user() === null) {
            return false;
        }

        $state = $this->getState();

        if (! $state) {
            return false;
        }

        $conversation = $this->resolve($state['name']);
        $questions = $this->buildQuestions($conversation);
        $question = $questions->get($state['index']);

        if ($question === null) {
            $this->clearState();

            return false;
        }

        $definition = QuestionAccessor::compile($question);
        $key = $definition->key($state['index']);

        $cancelCommand = $conversation->cancelCommand();
        $cancelTimeout = $conversation->cancelTimeout();

        // Inactivity timeout: cancel and let the new update flow to listens.
        if ($cancelTimeout !== null) {
            $idle = Tempora::now()->getTimestamp() - ($state['updated_at'] ?? $state['started_at']);

            if ($idle > $cancelTimeout) {
                $this->finishCancel($conversation, $request, 'timeout', $state['name']);

                return false;
            }
        }

        $text = text();

        // Cancel command consumes the update.
        if ($cancelCommand !== null && $text !== null && $this->commandEquals($text, $cancelCommand)) {
            $this->finishCancel($conversation, $request, 'command', $state['name']);

            return true;
        }

        // Skip command for the current question.
        if ($definition->skipCommand !== null && $text !== null && $this->commandEquals($text, $definition->skipCommand)) {
            $conversation->onSkip($request, $question);
            $this->events->dispatch(new QuestionSkipped($state['name'], $question));

            $state['answers'][$key] = ['type' => 'none', 'value' => null];
            $state['attempts'] = 0;

            $this->advance($conversation, $request, $questions, $state);

            return true;
        }

        [$matched, $value, $kind] = TypeMatcher::extract($definition->type);

        $errors = $this->validateAnswer($definition, $matched, $value);

        if ($errors !== []) {
            return $this->handleInvalid($conversation, $request, $question, $definition, $state, $errors);
        }

        // Valid answer.
        $state['answers'][$key] = ['type' => $kind, 'value' => $value];
        $state['attempts'] = 0;

        $answer = $this->makeAnswer($key, $state['answers'][$key]);

        $conversation->onAnswer($request, $question, $answer);
        $this->events->dispatch(new AnswerReceived($state['name'], $question, $answer));

        if ($definition->callback && ! $definition->deferred) {
            ($definition->callback)($request, $answer, $this->makeBag($state['answers']));
        }

        $this->advance($conversation, $request, $questions, $state);

        return true;
    }

    /**
     * Determine if the current user is in an active conversation.
     *
     * @return bool
     */
    public function active(): bool
    {
        return $this->getState() !== null;
    }

    /**
     * Get the raw state of the active conversation, if any.
     *
     * @return array|null
     */
    public function state(): ?array
    {
        return $this->getState();
    }

    /**
     * Get the answers collected so far (active or, when retained, completed).
     *
     * @return \LaraGram\Conversation\AnswersBag
     */
    public function answers(): AnswersBag
    {
        $state = $this->getState();

        $stored = $state !== null
            ? ($state['answers'] ?? [])
            : $this->store()->get($this->answersKey(), []);

        return $this->makeBag($stored);
    }

    /**
     * Build an Answer from a stored state entry.
     *
     * @param  int|string  $key
     * @param  array{type: string, value: mixed}  $entry
     * @return \LaraGram\Conversation\Answer
     */
    protected function makeAnswer(int|string $key, array $entry): Answer
    {
        return new Answer($key, $entry['type'], $entry['value'], $this->request());
    }

    /**
     * Build an AnswersBag from the stored answers map.
     *
     * @param  array<int|string, array{type: string, value: mixed}>  $stored
     * @return \LaraGram\Conversation\AnswersBag
     */
    protected function makeBag(array $stored): AnswersBag
    {
        $answers = [];

        foreach ($stored as $key => $entry) {
            $answers[$key] = $this->makeAnswer($key, $entry);
        }

        return new AnswersBag($answers);
    }

    /**
     * Cancel the active conversation for the current user.
     *
     * @param  string  $reason
     * @return void
     */
    public function cancel(string $reason = 'manual'): void
    {
        $state = $this->getState();

        if (! $state) {
            return;
        }

        $conversation = $this->resolve($state['name']);

        $this->finishCancel($conversation, $this->request(), $reason, $state['name']);
    }

    /**
     * Validate an extracted answer, returning a list of error messages.
     *
     * @param  \LaraGram\Conversation\QuestionDefinition  $question
     * @param  bool  $matched
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function validateAnswer(QuestionDefinition $question, bool $matched, mixed $value): array
    {
        if (! $matched) {
            return ["The answer must be of type [{$question->type}]."];
        }

        if ($question->rules === null) {
            return [];
        }

        $validator = $this->validator->make(
            ['answer' => $value],
            ['answer' => $question->rules],
            $question->messages
        );

        return $validator->fails() ? $validator->errors()->all() : [];
    }

    /**
     * Handle an invalid answer: count the attempt, re-ask or cancel.
     *
     * @param  array  $state
     * @param  array<int, string>  $errors
     * @return bool
     */
    protected function handleInvalid(
        Conversation $conversation,
        Request $request,
        Question $question,
        QuestionDefinition $definition,
        array $state,
        array $errors
    ): bool {
        $state['attempts'] = ($state['attempts'] ?? 0) + 1;
        $attempt = $state['attempts'];
        $maxAttempts = $definition->maxAttempts ?? $conversation->maxAttempts();

        $conversation->onInvalid($request, $question, $errors, $attempt);
        $this->events->dispatch(new AnswerInvalid($state['name'], $question, $errors, $attempt));

        if ($attempt >= $maxAttempts) {
            $this->finishCancel($conversation, $request, 'max_attempts', $state['name']);

            return true;
        }

        $this->putState($state);
        $this->askQuestion($conversation, $question, $state['name']);

        return true;
    }

    /**
     * Advance to the next question or complete the conversation.
     *
     * @param  array  $state
     * @return void
     */
    protected function advance(
        Conversation $conversation,
        Request $request,
        Questioner $questions,
        array $state
    ): void {
        $state['index'] = ($state['index'] ?? 0) + 1;

        if ($next = $questions->get($state['index'])) {
            $this->putState($state);
            $this->askQuestion($conversation, $next, $state['name']);

            return;
        }

        $this->complete($conversation, $request, $questions, $state);
    }

    /**
     * Complete the conversation: run deferred callbacks, fire hooks, persist.
     *
     * @param  array  $state
     * @return void
     */
    protected function complete(
        Conversation $conversation,
        Request $request,
        Questioner $questions,
        array $state
    ): void {
        $stored = $state['answers'] ?? [];
        $bag = $this->makeBag($stored);

        foreach ($questions->all() as $index => $question) {
            $definition = QuestionAccessor::compile($question);

            if ($definition->callback && $definition->deferred) {
                $key = $definition->key($index);

                if ($bag->has($key)) {
                    ($definition->callback)($request, $bag->get($key), $bag);
                }
            }
        }

        $conversation->onComplete($request, $bag);
        $this->events->dispatch(new ConversationCompleted($state['name'], $bag));

        $this->clearState();

        if ($conversation->forgetAfterComplete()) {
            $this->store()->forget($this->answersKey());
        } else {
            $this->store()->put($this->answersKey(), $stored, $this->lifetime());
        }
    }

    /**
     * Cancel the conversation and fire the cancel hook/event.
     *
     * @param  string  $reason
     * @param  string|null  $name
     * @return void
     */
    protected function finishCancel(Conversation $conversation, Request $request, string $reason, ?string $name): void
    {
        $conversation->onCancel($request, $reason);
        $this->events->dispatch(new ConversationCancelled($name, $reason));

        $this->clearState();
        $this->store()->forget($this->answersKey());
    }

    /**
     * Send a question's prompt to the user.
     *
     * @param  string  $name
     * @return void
     */
    protected function askQuestion(Conversation $conversation, Question $question, string $name): void
    {
        $request = $this->request();

        $definition = QuestionAccessor::compile($question);

        $conversation->onAsk($request, $question);
        $this->events->dispatch(new QuestionAsked($name, $question));

        if ($definition->sender) {
            ($definition->sender)($request, $question);

            return;
        }

        if ($definition->promptKind !== 'text' && $definition->promptMedia !== null) {
            $this->sendMedia($request, $definition);

            return;
        }

        $request->sendMessage(
            $this->chatId(),
            $definition->prompt,
            $definition->parseMode,
            $definition->keyboard
        );
    }

    /**
     * Media prompt delivery map: kind => [method, file parameter, captionable].
     *
     * @var array<string, array{0: string, 1: string, 2: bool}>
     */
    protected const MEDIA_METHODS = [
        'photo'      => ['sendPhoto', 'photo', true],
        'video'      => ['sendVideo', 'video', true],
        'audio'      => ['sendAudio', 'audio', true],
        'voice'      => ['sendVoice', 'voice', true],
        'document'   => ['sendDocument', 'document', true],
        'animation'  => ['sendAnimation', 'animation', true],
        'video_note' => ['sendVideoNote', 'video_note', false],
        'sticker'    => ['sendSticker', 'sticker', false],
    ];

    /**
     * Send a media-based question prompt (photo, video, voice, ...).
     *
     * Caption/parse mode are passed only to types that support a caption, and
     * arguments are passed by name so each method receives the right options
     * despite their differing parameter orders.
     *
     * @return void
     */
    protected function sendMedia(Request $request, QuestionDefinition $question): void
    {
        $map = self::MEDIA_METHODS[$question->promptKind] ?? null;

        if ($map === null) {
            $request->sendMessage($this->chatId(), $question->prompt, $question->parseMode, $question->keyboard);

            return;
        }

        [$method, $field, $captionable] = $map;

        $arguments = [
            'chat_id' => $this->chatId(),
            $field    => $question->promptMedia,
        ];

        if ($question->keyboard !== null) {
            $arguments['reply_markup'] = $question->keyboard;
        }

        if ($captionable) {
            if ($question->prompt !== '') {
                $arguments['caption'] = $question->prompt;
            }

            if ($question->parseMode !== null) {
                $arguments['parse_mode'] = $question->parseMode;
            }
        }

        $request->{$method}(...$arguments);
    }

    /**
     * Resolve a conversation instance from a name, class, or file.
     *
     * @param  string  $name
     * @return \LaraGram\Conversation\Conversation
     */
    protected function resolve(string $name): Conversation
    {
        if (class_exists($name)) {
            $instance = $this->container->make($name);
        } else {
            $path = $this->path($name);

            if (! is_file($path)) {
                throw new ConversationNotFoundException(
                    "Conversation [{$name}] not found at [{$path}]."
                );
            }

            $instance = require $path;
        }

        if (! $instance instanceof Conversation) {
            throw new ConversationNotFoundException(
                "Conversation [{$name}] must return a ".Conversation::class.' instance.'
            );
        }

        return $instance;
    }

    /**
     * Build a conversation's question list within the defining context so the
     * Conversation::create() facade form resolves to it.
     *
     * @return \LaraGram\Conversation\Questioner
     */
    protected function buildQuestions(Conversation $conversation): Questioner
    {
        $previous = $this->defining;
        $this->defining = $conversation;

        try {
            return $conversation->build();
        } finally {
            $this->defining = $previous;
        }
    }

    /**
     * Resolve the filesystem path for a conversation name.
     *
     * @param  string  $name
     * @return string
     */
    protected function path(string $name): string
    {
        $directory = $this->config->get('conversation.path') ?: app_path('Conversations');

        return rtrim($directory, '/\\').'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Compare incoming text against a command, tolerating a leading slash.
     *
     * @param  string  $text
     * @param  string  $command
     * @return bool
     */
    protected function commandEquals(string $text, string $command): bool
    {
        $text = trim($text);
        $command = ltrim($command, '/');

        return $text === $command || $text === '/'.$command;
    }

    /**
     * Resolve the chat id to reply to.
     *
     * @return mixed
     */
    protected function chatId(): mixed
    {
        return chat()?->id
            ?? callback_query()?->from->id
            ?? user()->id;
    }

    /**
     * Get the current request instance.
     *
     * @return \LaraGram\Request\Request
     */
    protected function request(): Request
    {
        return $this->container->make('request');
    }

    /**
     * Get the cache store backing conversation state.
     *
     * @return \LaraGram\Contracts\Cache\Repository
     */
    protected function store(): CacheRepository
    {
        return $this->container->make('cache')->store($this->config->get('conversation.store'));
    }

    /**
     * Get the cache key for the active conversation state.
     *
     * @return string
     */
    protected function stateKey(): string
    {
        return $this->prefix().':'.user()->id;
    }

    /**
     * Get the cache key for retained answers.
     *
     * @return string
     */
    protected function answersKey(): string
    {
        return $this->prefix().':answers:'.user()->id;
    }

    /**
     * Get the configured cache key prefix.
     *
     * @return string
     */
    protected function prefix(): string
    {
        return $this->config->get('conversation.prefix', 'conversation');
    }

    /**
     * Get the cache lifetime (seconds) for conversation state.
     *
     * @return int
     */
    protected function lifetime(): int
    {
        return (int) $this->config->get('conversation.lifetime', 3600);
    }

    /**
     * Persist the conversation state, refreshing the activity timestamp.
     *
     * @param  array  $state
     * @return void
     */
    protected function putState(array $state): void
    {
        $state['updated_at'] = Tempora::now()->getTimestamp();

        $this->store()->put($this->stateKey(), $state, $this->lifetime());
    }

    /**
     * Read the active conversation state.
     *
     * @return array|null
     */
    protected function getState(): ?array
    {
        return $this->store()->get($this->stateKey());
    }

    /**
     * Clear the active conversation state.
     *
     * @return void
     */
    protected function clearState(): void
    {
        $this->store()->forget($this->stateKey());
    }
}
