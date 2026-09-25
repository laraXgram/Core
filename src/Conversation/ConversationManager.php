<?php

namespace LaraGram\Conversation;

use Closure;
use LaraGram\Cache\Repository as CacheRepository;
use LaraGram\Contracts\Config\Repository as Config;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Conversation\Events\AnswerInvalid;
use LaraGram\Conversation\Events\BackRequested;
use LaraGram\Conversation\Events\AnswerReceived;
use LaraGram\Conversation\Events\ConversationCancelled;
use LaraGram\Conversation\Events\ConversationCompleted;
use LaraGram\Conversation\Events\ConversationStarted;
use LaraGram\Conversation\Events\QuestionAsked;
use LaraGram\Conversation\Events\QuestionSkipped;
use LaraGram\Contracts\Template\Factory as TemplateFactory;
use LaraGram\Request\Request;
use LaraGram\Support\Tempora;
use LaraGram\Template\Compilers\Temple8Compiler;
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
        $this->begin($this->resolve($name), [
            'name'       => $name,
            'parameters' => $parameters,
        ]);
    }

    /**
     * Begin a one-off inline conversation defined by a fluent builder.
     *
     * @param  \Closure  $builder
     * @return \LaraGram\Conversation\InlineConversationBuilder
     */
    public function inline(Closure $builder): InlineConversationBuilder
    {
        return new InlineConversationBuilder($this, $builder);
    }

    /**
     * Begin a single-question inline conversation.
     *
     * @param  string  $prompt
     * @param  string  $name
     * @return \LaraGram\Conversation\InlineConversationBuilder
     */
    public function ask(string|Closure $prompt, string $name = 'answer'): InlineConversationBuilder
    {
        return (new InlineConversationBuilder($this))->question($prompt, $name);
    }

    /**
     * Begin a single-question inline conversation offering a set of options.
     *
     * @param  string|\Closure  $prompt
     * @param  array<int|string, string>|\Closure  $options
     * @param  string  $name
     * @return \LaraGram\Conversation\InlineConversationBuilder
     */
    public function choose(string|Closure $prompt, array|Closure $options, string $name = 'answer'): InlineConversationBuilder
    {
        return $this->ask($prompt, $name)->choices($options);
    }

    /**
     * Begin a single-question inline conversation asking for a yes or no.
     *
     * @param  string|\Closure  $prompt
     * @param  string  $name
     * @return \LaraGram\Conversation\InlineConversationBuilder
     */
    public function confirm(string|Closure $prompt, string $name = 'answer'): InlineConversationBuilder
    {
        return $this->ask($prompt, $name)->confirm();
    }

    /**
     * Start a prepared inline conversation payload (called by the builder).
     *
     * @param  array  $payload
     * @param  array<string, mixed>  $parameters
     * @return void
     */
    public function startInline(array $payload, array $parameters = []): void
    {
        $this->begin(InlineConversation::fromPayload($payload), [
            'name'       => $payload['name'] ?? 'inline',
            'inline'     => $payload,
            'parameters' => $parameters,
        ]);
    }

    /**
     * Persist initial state and send the first question (or complete).
     *
     * @param  \LaraGram\Conversation\Conversation  $conversation
     * @param  array<string, mixed>  $seed
     * @return void
     */
    protected function begin(Conversation $conversation, array $seed): void
    {
        $now = Tempora::now()->getTimestamp();

        $state = array_merge([
            'name'              => 'conversation',
            'index'             => 0,
            'answers'           => [],
            'attempts'          => 0,
            'parameters'        => [],
            'path'              => [],
            'selected'          => [],
            'prompt_message_id' => null,
            'keyboard'          => null,
            'started_at'        => $now,
            'updated_at'        => $now,
        ], $seed);

        $this->putState($state);

        $request = $this->request();

        $conversation->withParameters($state['parameters']);

        $conversation->onStart($request);
        $this->events->dispatch(new ConversationStarted($state['name'], $conversation));

        $questions = $this->buildQuestions($conversation);

        $first = $this->nextApplicable($questions, $state, 0);

        if ($first === null) {
            // A conversation with no questions completes immediately.
            $this->complete($conversation, $request, $questions, $state);

            return;
        }

        $state['index'] = $first;

        $this->askQuestion($conversation, $questions, $state);
    }

    /**
     * Declare questions.
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
     * @return bool
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

        $conversation = $this->resolveFromState($state);
        $questions = $this->buildQuestions($conversation);
        $question = $questions->get($state['index']);

        if ($question === null) {
            $this->clearState();

            return false;
        }

        $definition = QuestionAccessor::compile($question);
        $key = $definition->key($state['index']);
        $answers = $this->makeBag($state['answers'] ?? []);

        // Inactivity timeout: cancel and let the new update flow to listens.
        if (($timeout = $conversation->cancelTimeout()) !== null) {
            $idle = Tempora::now()->getTimestamp() - ($state['updated_at'] ?? $state['started_at']);

            if ($idle > $timeout) {
                $this->finishCancel($conversation, $request, 'timeout', $state);

                return false;
            }
        }

        $text = text();
        $callbackData = callback_query()?->data;
        $cancelCommand = $conversation->cancelCommand();

        // Cancel command consumes the update.
        if ($cancelCommand !== null && $text !== null && $this->commandEquals($text, $cancelCommand)) {
            $this->finishCancel($conversation, $request, 'command', $state);

            return true;
        }

        // Back: return to the previous question that was actually asked.
        $back = Back::resolve($definition->back, $conversation->back());

        if ($back->matches($text, $callbackData)) {
            $this->acknowledge($request);

            return $this->goBack($conversation, $request, $questions, $state, $back);
        }

        // Skip: through the question's command, or its skip button.
        $skipped = ($definition->skipCommand !== null && $text !== null && $this->commandEquals($text, $definition->skipCommand))
            || ($definition->skipLabel !== null && $this->isSkip($text, $callbackData, $definition));

        if ($skipped) {
            $this->acknowledge($request);

            $conversation->onSkip($request, $question);
            $this->events->dispatch(new QuestionSkipped($state['name'], $question));

            $state['answers'][$key] = ['type' => 'none', 'value' => $definition->default];
            $state['attempts'] = 0;

            $this->advance($conversation, $request, $questions, $state);

            return true;
        }

        // A question with options is answered by its keyboard, not by its type.
        if ($definition->choices !== null) {
            return $this->handleChoice($conversation, $request, $questions, $question, $definition, $state, $answers, $text, $callbackData);
        }

        [$matched, $value, $kind] = TypeMatcher::extract($definition->type);

        $errors = $this->validateAnswer($definition, $matched, $value);

        if ($errors !== []) {
            return $this->handleInvalid($conversation, $request, $questions, $question, $definition, $state, $errors);
        }

        $this->acknowledge($request);

        return $this->accept($conversation, $request, $questions, $question, $definition, $state, $key, $kind, $value);
    }

    /**
     * Handle an update against a question that offers options.
     *
     * @param  array  $state
     * @return bool
     */
    protected function handleChoice(
        Conversation $conversation,
        Request $request,
        Questioner $questions,
        Question $question,
        QuestionDefinition $definition,
        array $state,
        AnswersBag $answers,
        ?string $text,
        ?string $callbackData
    ): bool {
        $choices = $definition->choices;
        $key = $definition->key($state['index']);

        // A multiple choice question collects taps until its done button.
        if ($choices->multiple) {
            $selected = $state['selected'] ?? [];

            if ($choices->isDone($text, $callbackData)) {
                $errors = $this->validateSelection($choices, $selected);

                if ($errors !== []) {
                    $this->acknowledge($request, $errors[0]);

                    return $this->handleInvalid($conversation, $request, $questions, $question, $definition, $state, $errors, reask: false);
                }

                $this->acknowledge($request);

                $state['selected'] = [];

                return $this->accept($conversation, $request, $questions, $question, $definition, $state, $key, 'choice', array_values($selected));
            }

            $value = $choices->resolve($text, $callbackData, $answers);

            if ($value === null) {
                return $this->handleInvalid($conversation, $request, $questions, $question, $definition, $state, [$this->invalidChoiceMessage()], reask: false);
            }

            $state['selected'] = in_array($value, $selected, false)
                ? array_values(array_filter($selected, fn ($selection) => (string) $selection !== (string) $value))
                : array_merge($selected, [$value]);

            $this->acknowledge($request);
            $this->putState($state);
            $this->refreshChoices($request, $conversation, $definition, $state, $answers);

            return true;
        }

        $value = $choices->resolve($text, $callbackData, $answers);

        // The keyboard is still on the screen, so a rejected tap only needs an
        // explanation, not the whole prompt again.
        if ($value === null) {
            return $this->handleInvalid($conversation, $request, $questions, $question, $definition, $state, [$this->invalidChoiceMessage()], reask: false);
        }

        $errors = $this->validateAnswer($definition, true, $value);

        if ($errors !== []) {
            return $this->handleInvalid($conversation, $request, $questions, $question, $definition, $state, $errors, reask: false);
        }

        $this->acknowledge($request);

        return $this->accept($conversation, $request, $questions, $question, $definition, $state, $key, 'choice', $value);
    }

    /**
     * Store an accepted answer, run its callbacks, and move on.
     *
     * @param  array  $state
     * @return bool
     */
    protected function accept(
        Conversation $conversation,
        Request $request,
        Questioner $questions,
        Question $question,
        QuestionDefinition $definition,
        array $state,
        int|string $key,
        string $kind,
        mixed $value
    ): bool {
        if ($definition->transform !== null) {
            $value = ($definition->transform)($value, $this->makeBag($state['answers'] ?? []));
        }

        $state['answers'][$key] = ['type' => $kind, 'value' => $value];
        $state['attempts'] = 0;

        $answer = $this->makeAnswer($key, $state['answers'][$key]);

        $flow = $conversation->onAnswer($request, $question, $answer);
        $this->events->dispatch(new AnswerReceived($state['name'], $question, $answer));

        if ($definition->callback && ! $definition->deferred) {
            $flow = ($definition->callback)($request, $answer, $this->makeBag($state['answers'])) ?? $flow;
        }

        if ($flow instanceof Flow) {
            $this->applyFlow($flow, $conversation, $request, $questions, $state);

            return true;
        }

        $this->advance($conversation, $request, $questions, $state);

        return true;
    }

    /**
     * Apply a flow instruction returned by a callback or a hook.
     *
     * @param  array  $state
     * @return void
     */
    protected function applyFlow(Flow $flow, Conversation $conversation, Request $request, Questioner $questions, array $state): void
    {
        switch ($flow->action) {
            case 'goto':
                $index = $questions->indexOf($flow->target);

                if ($index === null) {
                    throw new RuntimeException("Conversation question [{$flow->target}] was not found.");
                }

                $state['index'] = $index;
                $state['attempts'] = 0;
                $state['selected'] = [];

                $this->askQuestion($conversation, $questions, $state);

                return;

            case 'repeat':
                $state['attempts'] = 0;

                $this->askQuestion($conversation, $questions, $state);

                return;

            case 'cancel':
                $this->finishCancel($conversation, $request, $flow->reason ?? 'manual', $state);

                return;

            default:
                $this->complete($conversation, $request, $questions, $state);
        }
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
     * Determine whether the active conversation's current question should handle
     * the update BEFORE regular/step listens (Priority::Conversation). Otherwise
     * the conversation is a fallback that runs only when no listen matches.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return bool
     */
    public function prefersConversation(Request $request): bool
    {
        if (user() === null) {
            return false;
        }

        $state = $this->getState();

        if (! $state) {
            return false;
        }

        $conversation = $this->resolveFromState($state);
        $question = $this->buildQuestions($conversation)->get($state['index']);

        if ($question === null) {
            return false;
        }

        $priority = QuestionAccessor::compile($question)->priority
            ?? $conversation->priority()
            ?? Priority::Listen;

        return $priority === Priority::Conversation;
    }

    /**
     * Determine whether the conversation should handle the update as the
     * fallback - active, and no regular/step listen matches (so it runs before
     * the application's own fallback listens).
     *
     * @param  \LaraGram\Request\Request  $request
     * @return bool
     */
    public function handlesUpdateAsFallback(Request $request): bool
    {
        if (! $this->active()) {
            return false;
        }

        return ! $this->container->make('listener')->matchesNonFallback($request);
    }

    /**
     * Stop and forget the active conversation because another listen is taking
     * over the update. Reached only when a regular/step listen matched while a
     * conversation was active (and it was not given conversation priority).
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function interruptIfActive(Request $request): void
    {
        if (user() === null || ! $this->active()) {
            return;
        }

        $this->cancel('interrupted');
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

        $conversation = $this->resolveFromState($state);

        $this->finishCancel($conversation, $this->request(), $reason, $state);
    }

    /**
     * Resolve the conversation instance backing the given state (file or inline).
     *
     * @param  array  $state
     * @return \LaraGram\Conversation\Conversation
     */
    protected function resolveFromState(array $state): Conversation
    {
        $conversation = isset($state['inline'])
            ? InlineConversation::fromPayload($state['inline'])
            : $this->resolve($state['name']);

        return $conversation->withParameters($state['parameters'] ?? []);
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
     * Validate the number of options selected on a multiple choice question.
     *
     * @param  array<int, mixed>  $selected
     * @return array<int, string>
     */
    protected function validateSelection(Choices $choices, array $selected): array
    {
        if ($choices->min !== null && count($selected) < $choices->min) {
            return ["Select at least {$choices->min} option(s)."];
        }

        if ($choices->max !== null && count($selected) > $choices->max) {
            return ["Select at most {$choices->max} option(s)."];
        }

        return [];
    }

    /**
     * Get the message sent when an update matches none of the options.
     *
     * @return string
     */
    protected function invalidChoiceMessage(): string
    {
        return (string) $this->config->get('conversation.invalid_choice', 'Please choose one of the options.');
    }

    /**
     * Handle an invalid answer: count the attempt, explain, re-ask or cancel.
     *
     * @param  array  $state
     * @param  array<int, string>  $errors
     * @return bool
     */
    protected function handleInvalid(
        Conversation $conversation,
        Request $request,
        Questioner $questions,
        Question $question,
        QuestionDefinition $definition,
        array $state,
        array $errors,
        bool $reask = true
    ): bool {
        $state['attempts'] = ($state['attempts'] ?? 0) + 1;
        $attempt = $state['attempts'];
        $maxAttempts = $definition->maxAttempts ?? $conversation->maxAttempts();

        $conversation->onInvalid($request, $question, $errors, $attempt);
        $this->events->dispatch(new AnswerInvalid($state['name'], $question, $errors, $attempt));

        if ($attempt >= $maxAttempts) {
            $this->finishCancel($conversation, $request, 'max_attempts', $state);

            return true;
        }

        if (($message = $this->retryMessage($conversation, $definition, $errors, $attempt)) !== null) {
            $request->sendMessage($this->chatId(), $message);
        }

        if ($reask) {
            $this->askQuestion($conversation, $questions, $state);
        } else {
            $this->putState($state);
        }

        return true;
    }

    /**
     * Resolve the message explaining why an answer was rejected.
     *
     * @param  array<int, string>  $errors
     * @param  int  $attempt
     * @return string|null
     */
    protected function retryMessage(
        Conversation $conversation,
        QuestionDefinition $definition,
        array $errors,
        int $attempt
    ): ?string {
        $message = $definition->retry ?? $conversation->retryMessage();

        if ($message instanceof Closure) {
            $message = $message($errors, $attempt);
        }

        if ($message === false || $message === null) {
            return null;
        }

        return $message === true ? ($errors[0] ?? null) : (string) $message;
    }

    /**
     * Return to the previous question that was asked, clearing its answer.
     *
     * @param  array  $state
     * @return bool
     */
    protected function goBack(
        Conversation $conversation,
        Request $request,
        Questioner $questions,
        array $state,
        Back $back
    ): bool {
        $path = $state['path'] ?? [$state['index']];

        array_pop($path);

        $target = $path === [] ? null : $path[count($path) - 1];

        if ($target === null) {
            if ($back->onFirst === 'cancel') {
                $this->finishCancel($conversation, $request, 'back', $state);

                return true;
            }

            return true;
        }

        $previous = $questions->get($target);

        // Clear the previous answer so it is asked (and answered) again.
        unset($state['answers'][QuestionAccessor::compile($previous)->key($target)]);

        $state['index'] = $target;
        $state['path'] = $path;
        $state['attempts'] = 0;
        $state['selected'] = [];

        $flow = $conversation->onBack($request, $previous);
        $this->events->dispatch(new BackRequested($state['name'], $previous));

        if ($flow instanceof Flow) {
            $this->applyFlow($flow, $conversation, $request, $questions, $state);

            return true;
        }

        $this->askQuestion($conversation, $questions, $state);

        return true;
    }

    /**
     * Advance to the next applicable question or complete the conversation.
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
        $next = $this->nextApplicable($questions, $state, ($state['index'] ?? 0) + 1);

        if ($next === null) {
            $this->complete($conversation, $request, $questions, $state);

            return;
        }

        $state['index'] = $next;
        $state['selected'] = [];

        $this->askQuestion($conversation, $questions, $state);
    }

    /**
     * Get the index of the first question from the given position whose
     * condition passes.
     *
     * @param  array  $state
     * @return int|null
     */
    protected function nextApplicable(Questioner $questions, array $state, int $from): ?int
    {
        $answers = $this->makeBag($state['answers'] ?? []);

        for ($index = max(0, $from); $index < $questions->count(); $index++) {
            if (QuestionAccessor::compile($questions->get($index))->applies($answers)) {
                return $index;
            }
        }

        return null;
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

        $this->clearKeyboard($request, $conversation, $state);

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
     * @param  array  $state
     * @return void
     */
    protected function finishCancel(Conversation $conversation, Request $request, string $reason, array $state): void
    {
        $this->clearKeyboard($request, $conversation, $state);

        $conversation->onCancel($request, $reason);
        $this->events->dispatch(new ConversationCancelled($state['name'] ?? null, $reason));

        $this->clearState();
        $this->store()->forget($this->answersKey());
    }

    /**
     * Take back the keyboard of the last prompt once the conversation is over.
     *
     * An inline keyboard is removed from the message it belongs to; a reply
     * keyboard needs a message of its own, which is only sent when the
     * conversation asks for it.
     *
     * @param  array  $state
     * @return void
     */
    protected function clearKeyboard(Request $request, Conversation $conversation, array $state): void
    {
        $clear = $conversation->clearKeyboard();

        if ($clear === false || ($state['keyboard'] ?? null) === null) {
            return;
        }

        if ($state['keyboard'] === 'inline') {
            if (($messageId = $state['prompt_message_id'] ?? null) !== null) {
                $request->editMessageReplyMarkup(
                    chat_id: $this->chatId(),
                    message_id: $messageId,
                    reply_markup: json_encode(['inline_keyboard' => []])
                );
            }

            return;
        }

        $text = is_string($clear) ? $clear : $this->config->get('conversation.keyboard_cleared_text');

        if ($text === null || $text === '') {
            return;
        }

        $request->sendMessage(
            $this->chatId(),
            $text,
            reply_markup: json_encode(['remove_keyboard' => true])
        );
    }

    /**
     * Send the prompt of the question the state points at.
     *
     * @param  array  $state
     * @return void
     */
    protected function askQuestion(Conversation $conversation, Questioner $questions, array $state): void
    {
        $index = $state['index'];
        $question = $questions->get($index);

        if ($question === null) {
            $this->complete($conversation, $this->request(), $questions, $state);

            return;
        }

        $request = $this->request();
        $definition = QuestionAccessor::compile($question);
        $answers = $this->makeBag($state['answers'] ?? []);

        $path = $state['path'] ?? [];

        if (($path[count($path) - 1] ?? null) !== $index) {
            $path[] = $index;
        }

        $state['path'] = $path;
        $state['selected'] = $state['selected'] ?? [];

        $conversation->onAsk($request, $question);
        $this->events->dispatch(new QuestionAsked($state['name'], $question));

        if ($definition->sender) {
            ($definition->sender)($request, $question);

            $this->putState($state);

            return;
        }

        $keyboard = $this->promptKeyboard($conversation, $definition, $state, $answers);

        $result = $definition->template !== null
            ? $this->sendTemplate($request, $conversation, $definition, $state, $answers, $keyboard)
            : $this->sendPrompt($request, $definition, $answers, $keyboard);

        $state['prompt_message_id'] = $this->messageIdFrom($result);
        $state['keyboard'] = $this->keyboardKind($keyboard);

        $this->putState($state);
    }

    /**
     * Build the keyboard sent with a question: its options or its own keyboard,
     * plus the skip and back controls.
     *
     * @param  array  $state
     * @return array<string, mixed>|null
     */
    protected function promptKeyboard(
        Conversation $conversation,
        QuestionDefinition $definition,
        array $state,
        AnswersBag $answers
    ): ?array {
        $keyboard = $definition->choices !== null
            ? $definition->choices->keyboard($state['selected'] ?? [], $answers)
            : $this->normalizeKeyboard($definition->keyboard);

        $keyboard = $this->attachControls(
            $keyboard, $this->controlRows($conversation, $definition, $state, $keyboard)
        );

        // A reply keyboard stays on the screen until another one replaces it,
        // so a prompt that has none of its own takes the previous one back.
        if ($keyboard === null && ($state['keyboard'] ?? null) === 'reply') {
            return ['remove_keyboard' => true];
        }

        return $keyboard;
    }

    /**
     * Build the rows of the skip and back controls, in the layout of the
     * keyboard they are attached to.
     *
     * @param  array  $state
     * @param  array<string, mixed>|null  $keyboard
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function controlRows(
        Conversation $conversation,
        QuestionDefinition $definition,
        array $state,
        ?array $keyboard
    ): array {
        $back = Back::resolve($definition->back, $conversation->back());

        // Controls follow the keyboard they are attached to. Without one they
        // are inline, unless a reply keyboard is on the screen and would
        // otherwise be left behind, or the control asks for a reply button.
        $layout = $this->keyboardKind($keyboard)
            ?? match (true) {
                $back->mode === 'reply' => 'reply',
                ($state['keyboard'] ?? null) === 'reply' => 'reply',
                default => 'inline',
            };

        $button = fn (string $label, string $data) => $layout === 'inline'
            ? ['text' => $label, 'callback_data' => $data]
            : ['text' => $label];

        $rows = [];

        if ($definition->skipLabel !== null) {
            $rows[] = [$button($definition->skipLabel, self::SKIP)];
        }

        if ($back->injects() && $this->canGoBack($state, $back)) {
            $rows[] = [$button($back->label, $back->callbackData)];
        }

        return $rows;
    }

    /**
     * Determine whether the question being asked has somewhere to go back to.
     *
     * @param  array  $state
     * @return bool
     */
    protected function canGoBack(array $state, Back $back): bool
    {
        if (count($state['path'] ?? []) > 1) {
            return true;
        }

        return $back->onFirst === 'cancel';
    }

    /**
     * Attach the control rows to a keyboard, building one when there is none.
     *
     * @param  array<string, mixed>|null  $keyboard
     * @param  array<int, array<int, array<string, mixed>>>  $rows
     * @return array<string, mixed>|null
     */
    protected function attachControls(?array $keyboard, array $rows): ?array
    {
        if ($rows === []) {
            return $keyboard;
        }

        if ($keyboard === null) {
            return isset($rows[0][0]['callback_data'])
                ? ['inline_keyboard' => $rows]
                : ['keyboard' => $rows, 'resize_keyboard' => true];
        }

        $key = isset($keyboard['keyboard']) ? 'keyboard' : 'inline_keyboard';

        $keyboard[$key] = array_merge($keyboard[$key] ?? [], $rows);

        return $keyboard;
    }

    /**
     * Get the kind of a keyboard: inline, reply, or none.
     *
     * @param  array<string, mixed>|null  $keyboard
     * @return string|null
     */
    protected function keyboardKind(?array $keyboard): ?string
    {
        return match (true) {
            $keyboard === null => null,
            isset($keyboard['inline_keyboard']) => 'inline',
            isset($keyboard['keyboard']) => 'reply',
            default => null,
        };
    }

    /**
     * Normalize a keyboard value to an array.
     *
     * @param  mixed  $keyboard
     * @return array<string, mixed>|null
     */
    protected function normalizeKeyboard(mixed $keyboard): ?array
    {
        if ($keyboard === null) {
            return null;
        }

        if (is_array($keyboard)) {
            return $keyboard;
        }

        if (is_string($keyboard)) {
            $decoded = json_decode($keyboard, true);

            return is_array($decoded) ? $decoded : null;
        }

        if (is_object($keyboard) && method_exists($keyboard, 'get')) {
            $value = $keyboard->get(true);

            return is_array($value) ? $value : null;
        }

        return null;
    }

    /**
     * Send a question's prompt as a message or a media message.
     *
     * @param  array<string, mixed>|null  $keyboard
     * @return mixed
     */
    protected function sendPrompt(
        Request $request,
        QuestionDefinition $definition,
        AnswersBag $answers,
        ?array $keyboard
    ): mixed {
        $prompt = $definition->prompt($answers);
        $markup = $keyboard === null ? null : json_encode($keyboard);

        if ($definition->promptKind !== 'text' && $definition->promptMedia !== null) {
            return $this->sendMedia($request, $definition, $prompt, $markup);
        }

        return $request->sendMessage($this->chatId(), $prompt, $definition->parseMode, $markup);
    }

    /**
     * Render a question's template and send the message it builds.
     *
     * The template owns the message - text, parse mode, keyboard, media, rich
     * message - and the conversation only adds its own controls to the keyboard
     * the template produced.
     *
     * @param  array  $state
     * @param  array<string, mixed>|null  $keyboard
     * @return mixed
     */
    protected function sendTemplate(
        Request $request,
        Conversation $conversation,
        QuestionDefinition $definition,
        array $state,
        AnswersBag $answers,
        ?array $keyboard
    ): mixed {
        $template = $definition->template;

        $data = array_merge($template['data'], [
            'answers' => $answers,
            'conversation' => $state['name'],
            'parameters' => $state['parameters'] ?? [],
            'prompt' => $definition->prompt($answers),
            'choices' => $this->choiceButtons($definition, $state, $answers),
            'step' => count($state['path'] ?? []),
            'steps' => $this->stepCount($state),
        ]);

        $calls = Request::recordCalls(fn () => $this->renderTemplate($template, $data));

        if ($calls === []) {
            throw new RuntimeException(
                "The conversation template [{$template['template']}] did not produce a message."
            );
        }

        $controls = $this->controlRows($conversation, $definition, $state, null);
        $result = null;

        foreach ($calls as $index => $call) {
            $parameters = $call['parameters'];

            if ($index === 0) {
                $parameters['reply_markup'] = $this->mergeTemplateKeyboard(
                    $parameters['reply_markup'] ?? null, $keyboard, $controls
                );

                if ($parameters['reply_markup'] === null) {
                    unset($parameters['reply_markup']);
                }
            }

            $outcome = $request->{$call['method']}(...$parameters);

            $result ??= $outcome;
        }

        return $result;
    }

    /**
     * Count the questions a conversation has, for a template showing progress.
     *
     * @param  array  $state
     * @return int
     */
    protected function stepCount(array $state): int
    {
        $conversation = $this->resolveFromState($state);

        return $this->buildQuestions($conversation)->count();
    }

    /**
     * Describe a question's options for the template rendering its prompt.
     *
     * Each entry carries the value, the label, the callback data of its button
     * and whether it is already selected, so a template may lay the options out
     * however it likes.
     *
     * @param  array  $state
     * @return array<int, array{value: int|string, label: string, data: string, selected: bool}>
     */
    protected function choiceButtons(QuestionDefinition $definition, array $state, AnswersBag $answers): array
    {
        if ($definition->choices === null) {
            return [];
        }

        $selected = $state['selected'] ?? [];
        $buttons = [];
        $index = 0;

        foreach ($definition->choices->options($answers) as $value => $label) {
            $buttons[] = [
                'value' => $value,
                'label' => $label,
                'data' => Choices::PREFIX.$index,
                'selected' => in_array($value, $selected, false),
            ];

            $index++;
        }

        return $buttons;
    }

    /**
     * Merge the keyboard a template produced with the question's own keyboard
     * and the conversation controls.
     *
     * @param  mixed  $rendered
     * @param  array<string, mixed>|null  $keyboard
     * @param  array<int, array<int, array<string, mixed>>>  $controls
     * @return string|null
     */
    protected function mergeTemplateKeyboard(mixed $rendered, ?array $keyboard, array $controls): ?string
    {
        $base = $this->normalizeKeyboard($rendered) ?? $this->stripControls($keyboard, $controls);

        $merged = $this->attachControls($base, $this->relayout($controls, $this->keyboardKind($base)));

        return $merged === null ? null : json_encode($merged);
    }

    /**
     * Get the question's own keyboard without the controls already added to it.
     *
     * @param  array<string, mixed>|null  $keyboard
     * @param  array<int, mixed>  $controls
     * @return array<string, mixed>|null
     */
    protected function stripControls(?array $keyboard, array $controls): ?array
    {
        if ($keyboard === null || $controls === []) {
            return $keyboard;
        }

        $key = isset($keyboard['keyboard']) ? 'keyboard' : 'inline_keyboard';
        $rows = array_slice($keyboard[$key] ?? [], 0, -count($controls));

        if ($rows === []) {
            return null;
        }

        $keyboard[$key] = $rows;

        return $keyboard;
    }

    /**
     * Rebuild the control rows for another keyboard layout.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $rows
     * @param  string|null  $layout
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function relayout(array $rows, ?string $layout): array
    {
        if ($layout !== 'reply') {
            return $rows;
        }

        return array_map(
            fn (array $row) => array_map(fn (array $button) => ['text' => $button['text']], $row),
            $rows
        );
    }

    /**
     * Render a question's template from its source.
     *
     * @param  array{template: string, data: array, source: string}  $template
     * @param  array<string, mixed>  $data
     * @return void
     */
    protected function renderTemplate(array $template, array $data): void
    {
        $factory = $this->container->make(TemplateFactory::class);

        match ($template['source'] ?? 'name') {
            'inline' => Temple8Compiler::render($template['template'], $data),
            'path' => $factory->file($template['template'], $data)->render(),
            default => $factory->make($template['template'], $data)->render(),
        };
    }

    /**
     * Redraw the keyboard of a multiple choice question after a selection.
     *
     * @param  array  $state
     * @return void
     */
    protected function refreshChoices(
        Request $request,
        Conversation $conversation,
        QuestionDefinition $definition,
        array $state,
        AnswersBag $answers
    ): void {
        $messageId = $state['prompt_message_id'] ?? null;

        if ($messageId === null || ($state['keyboard'] ?? null) !== 'inline') {
            return;
        }

        $request->editMessageReplyMarkup(
            chat_id: $this->chatId(),
            message_id: $messageId,
            reply_markup: json_encode($this->promptKeyboard($conversation, $definition, $state, $answers))
        );
    }

    /**
     * Answer the callback query behind the current update, if there is one, so
     * the button the user tapped stops spinning.
     *
     * @param  string|null  $text
     * @return void
     */
    protected function acknowledge(Request $request, ?string $text = null): void
    {
        $callback = callback_query();

        if ($callback === null || ! isset($callback->id)) {
            return;
        }

        $request->answerCallbackQuery($callback->id, $text);
    }

    /**
     * Determine whether the update asks to skip the current question.
     *
     * @param  string|null  $text
     * @param  string|null  $callbackData
     * @return bool
     */
    protected function isSkip(?string $text, ?string $callbackData, QuestionDefinition $definition): bool
    {
        if ($callbackData === self::SKIP) {
            return true;
        }

        return $text !== null && $definition->skipLabel !== null && trim($text) === $definition->skipLabel;
    }

    /**
     * Read the message identifier out of a Bot API result.
     *
     * @param  mixed  $result
     * @return int|null
     */
    protected function messageIdFrom(mixed $result): ?int
    {
        $result = is_string($result) ? json_decode($result, true) : $result;

        if (is_object($result)) {
            $result = json_decode(json_encode($result), true);
        }

        $id = is_array($result) ? ($result['result']['message_id'] ?? null) : null;

        return $id === null ? null : (int) $id;
    }

    /**
     * The callback data of the skip button.
     *
     * @var string
     */
    protected const SKIP = 'conversation:skip';

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
     * @param  string  $prompt
     * @param  string|null  $replyMarkup
     * @return mixed
     */
    protected function sendMedia(Request $request, QuestionDefinition $question, string $prompt, ?string $replyMarkup = null): mixed
    {
        $map = self::MEDIA_METHODS[$question->promptKind] ?? null;

        if ($map === null) {
            return $request->sendMessage($this->chatId(), $prompt, $question->parseMode, $replyMarkup);
        }

        [$method, $field, $captionable] = $map;

        $arguments = [
            'chat_id' => $this->chatId(),
            $field    => $question->promptMedia,
        ];

        if ($replyMarkup !== null) {
            $arguments['reply_markup'] = $replyMarkup;
        }

        if ($captionable) {
            if ($prompt !== '') {
                $arguments['caption'] = $prompt;
            }

            if ($question->parseMode !== null) {
                $arguments['parse_mode'] = $question->parseMode;
            }
        }

        return $request->{$method}(...$arguments);
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
            ?? user()?->id;
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
        return $this->prefix().':'.$this->owner();
    }

    /**
     * Get the cache key for retained answers.
     *
     * @return string
     */
    protected function answersKey(): string
    {
        return $this->prefix().':answers:'.$this->owner();
    }

    /**
     * Get the owner segment of the cache keys.
     *
     * When several bots share the application (the 'auto' connection) the user
     * is scoped to the bot, so one user's conversations in different bots never
     * collide.
     *
     * @return string
     */
    protected function owner(): string
    {
        $id = user()->id;

        return $this->config->get('bot.default') === 'auto' && ! is_null($bot = $this->request()->botConnection())
            ? $bot.':'.$id
            : (string) $id;
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
