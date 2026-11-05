<?php

namespace LaraGram\Listening\Navigation;

use Closure;
use InvalidArgumentException;
use LaraGram\Contracts\Config\Repository as Config;
use LaraGram\Contracts\Container\Container;
use LaraGram\Listening\Exceptions\ListenNotFoundException;
use LaraGram\Listening\Listen;
use LaraGram\Listening\Listener;
use LaraGram\Request\Request;
use LaraGram\Request\Response;
use LaraGram\Support\Facades\Facade;

class Navigator
{
    /**
     * The callback data prefix of the buttons that take the user back.
     */
    public const BUTTON_PREFIX = 'lg:back:';

    /**
     * The number of steps being taken back right now.
     *
     * @var int
     */
    protected $replaying = 0;

    /**
     * Create a new navigator instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @param  \LaraGram\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(
        protected Container $container,
        protected Config $config,
    ) {
    }

    /**
     * Record the update being handled as a step of the user's trail.
     *
     * A named step the user already passed cuts the trail back to it, and a
     * step that repeats the last one takes its place.
     *
     * @param  string|null  $name
     * @param  string|null  $title
     * @param  \LaraGram\Listening\Listen|null  $listen
     * @return \LaraGram\Listening\Navigation\Breadcrumb|null
     */
    public function push(?string $name = null, ?string $title = null, ?Listen $listen = null): ?Breadcrumb
    {
        $key = $this->key();

        if ($this->replaying > 0 || $key === null) {
            return null;
        }

        $request = $this->request();
        $listen ??= $request->listen();

        $crumb = new Breadcrumb(
            $name ??= $listen?->getName(),
            $title,
            $this->fingerprint($listen),
            $this->updateOf($request),
            $this->currentState(),
            time(),
        );

        $trail = $this->withoutRepeatOf($this->load($key), $crumb);

        $trail[] = $crumb;

        $this->save($key, array_slice($trail, -max(1, (int) $this->config->get('navigation.limit', 50))));

        return $crumb;
    }

    /**
     * Get the callback data of a button that takes the user back.
     *
     * @param  int|string  $to
     * @param  string|null  $fallback
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function buttonData(int|string $to = 1, ?string $fallback = null): string
    {
        $data = self::BUTTON_PREFIX.$to.($fallback === null ? '' : '|'.$fallback);

        if (strlen($data) > 64) {
            throw new InvalidArgumentException("The back button data [{$data}] is longer than Telegram's limit of 64 bytes.");
        }

        return $data;
    }

    /**
     * Get what a button's callback data asks for, or null when it is not a back button.
     *
     * @param  string|null  $data
     * @return array{to: int|string, fallback: string|null}|null
     */
    public static function parseButton(?string $data): ?array
    {
        if ($data === null || ! str_starts_with($data, self::BUTTON_PREFIX)) {
            return null;
        }

        [$to, $fallback] = array_pad(explode('|', substr($data, strlen(self::BUTTON_PREFIX)), 2), 2, null);

        return ['to' => ctype_digit($to) ? (int) $to : $to, 'fallback' => $fallback];
    }

    /**
     * Create a response that takes the user back.
     *
     * @param  int|string  $to  The number of steps, or the name of a step.
     * @param  \Closure|string|null  $fallback  A listen name or a callback to run when there is nothing to go back to.
     * @return \LaraGram\Listening\Navigation\BackResponse
     */
    public function back(int|string $to = 1, Closure|string|null $fallback = null): BackResponse
    {
        return new BackResponse($this, $to, $fallback);
    }

    /**
     * Take the user back and get the response of the step they land on.
     *
     * @param  int|string  $to
     * @param  \Closure|string|null  $fallback
     * @param  \LaraGram\Request\Request|null  $request
     * @return \LaraGram\Request\Response
     */
    public function navigate(int|string $to = 1, Closure|string|null $fallback = null, ?Request $request = null): Response
    {
        $request ??= $this->request();

        $key = $this->key();
        $trail = $key === null ? [] : $this->withoutCurrentUpdate($this->load($key), $request);

        $target = $this->targetIndex($trail, $to);

        if ($target === null) {
            if ($key !== null) {
                $this->save($key, $trail);
            }

            return $this->runFallback($fallback, $request);
        }

        $crumb = $trail[$target];

        $this->save($key, array_slice($trail, 0, $target + 1));

        try {
            return $this->replay($crumb, $request);
        } catch (ListenNotFoundException) {
            // The listen of this step is gone, so it cannot be returned to.
            return $this->runFallback($fallback, $request);
        }
    }

    /**
     * Get the steps of the user's trail, the first one first.
     *
     * @return list<\LaraGram\Listening\Navigation\Breadcrumb>
     */
    public function trail(): array
    {
        $key = $this->key();

        return $key === null ? [] : $this->load($key);
    }

    /**
     * Get the trail as text, leaving out the steps without a name or a title.
     *
     * @param  string  $separator
     * @return string
     */
    public function breadcrumbs(string $separator = ' › '): string
    {
        return implode($separator, array_filter(
            array_map(fn (Breadcrumb $crumb) => $crumb->label(), $this->trail()),
            fn ($label) => $label !== null && $label !== '',
        ));
    }

    /**
     * Get the step the user is on.
     *
     * @return \LaraGram\Listening\Navigation\Breadcrumb|null
     */
    public function current(): ?Breadcrumb
    {
        $trail = $this->trail();

        return $trail === [] ? null : $trail[array_key_last($trail)];
    }

    /**
     * Get the step the user would go back to.
     *
     * @return \LaraGram\Listening\Navigation\Breadcrumb|null
     */
    public function previous(): ?Breadcrumb
    {
        $trail = $this->trail();

        return $trail[count($trail) - 2] ?? null;
    }

    /**
     * Determine if the user can go back to the given step.
     *
     * @param  int|string  $to
     * @return bool
     */
    public function canGoBack(int|string $to = 1): bool
    {
        return $this->targetIndex($this->trail(), $to) !== null;
    }

    /**
     * Determine if the update being handled got here by going back.
     *
     * @return bool
     */
    public function isGoingBack(): bool
    {
        return $this->replaying > 0;
    }

    /**
     * Forget the user's trail.
     *
     * @return void
     */
    public function clear(): void
    {
        if (($key = $this->key()) !== null) {
            $this->store()->forget($key);
        }
    }

    /**
     * Run the step's update again as the user's current update.
     *
     * @param  \LaraGram\Listening\Navigation\Breadcrumb  $crumb
     * @param  \LaraGram\Request\Request  $current
     * @return \LaraGram\Request\Response
     */
    protected function replay(Breadcrumb $crumb, Request $current): Response
    {
        $update = $this->updateOf($current);

        $request = Request::createFromBase([
            '',
            json_encode($this->freshen($crumb->update, $update)),
            json_encode($current->server()),
        ]);

        if (($connection = $current->getBoundConnection()) !== null) {
            $request->useConnection($connection);
        }

        $this->restoreState($crumb->state);
        $this->bindRequest($request);
        $this->replaying++;

        try {
            $response = $this->container->make(Listener::class)->dispatchToListen($request);
        } finally {
            $this->replaying--;
            $this->bindRequest($current);
        }

        // Nothing in the step can answer a button press it knows nothing about.
        if (isset($update['callback_query']) && ! isset($crumb->update['callback_query'])) {
            $current->silent()->answerCallbackQuery($update['callback_query']['id']);
        }

        return $response;
    }

    /**
     * Point a step's update at the update that took the user back.
     *
     * A button press keeps the id and the message of the press being handled,
     * so answering it or editing its message acts on what the user just tapped.
     *
     * @param  array  $update
     * @param  array  $current
     * @return array
     */
    protected function freshen(array $update, array $current): array
    {
        $update['update_id'] = $current['update_id'] ?? $update['update_id'] ?? null;

        if (isset($update['callback_query'], $current['callback_query'])) {
            $update['callback_query']['id'] = $current['callback_query']['id'];

            if (isset($current['callback_query']['message'])) {
                $update['callback_query']['message'] = $current['callback_query']['message'];
            }
        }

        return $update;
    }

    /**
     * Run what was given for when there is nothing to go back to.
     *
     * @param  \Closure|string|null  $fallback
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Request\Response
     */
    protected function runFallback(Closure|string|null $fallback, Request $request): Response
    {
        if ($fallback instanceof Closure) {
            return Listener::toResponse($request, $this->container->call($fallback));
        }

        if ($fallback !== null) {
            return $this->container->make(Listener::class)->respondWithListen($fallback);
        }

        // Nowhere to go, but the button press must not keep spinning.
        if (isset($request->update()['callback_query'])) {
            $request->silent()->answerCallbackQuery($this->updateOf($request)['callback_query']['id']);
        }

        return new Response;
    }

    /**
     * Get the index of the step the given target leads to.
     *
     * @param  list<\LaraGram\Listening\Navigation\Breadcrumb>  $trail
     * @param  int|string  $to
     * @return int|null
     */
    protected function targetIndex(array $trail, int|string $to): ?int
    {
        if (is_string($to)) {
            for ($index = count($trail) - 1; $index >= 0; $index--) {
                if ($trail[$index]->name === $to) {
                    return $index;
                }
            }

            return null;
        }

        $last = count($trail) - 1;

        // Going back further than the first step lands on the first step.
        return $last < 0 || ($last === 0 && $to > 0) ? null : max(0, $last - max(0, $to));
    }

    /**
     * Remove what a new step replaces from the trail.
     *
     * @param  list<\LaraGram\Listening\Navigation\Breadcrumb>  $trail
     * @param  \LaraGram\Listening\Navigation\Breadcrumb  $crumb
     * @return list<\LaraGram\Listening\Navigation\Breadcrumb>
     */
    protected function withoutRepeatOf(array $trail, Breadcrumb $crumb): array
    {
        if ($crumb->name !== null) {
            foreach ($trail as $index => $existing) {
                if ($existing->name === $crumb->name) {
                    return array_slice($trail, 0, $index);
                }
            }
        }

        if ($trail !== [] && $trail[array_key_last($trail)]->fingerprint === $crumb->fingerprint) {
            array_pop($trail);
        }

        return $trail;
    }

    /**
     * Remove the step recorded by the update being handled.
     *
     * A tracked back button records itself before its handler asks to go back,
     * and it is not a step the user wants to count.
     *
     * @param  list<\LaraGram\Listening\Navigation\Breadcrumb>  $trail
     * @param  \LaraGram\Request\Request  $request
     * @return list<\LaraGram\Listening\Navigation\Breadcrumb>
     */
    protected function withoutCurrentUpdate(array $trail, Request $request): array
    {
        $updateId = $this->updateOf($request)['update_id'] ?? null;

        if ($trail !== [] && $updateId !== null && ($trail[array_key_last($trail)]->update['update_id'] ?? null) === $updateId) {
            array_pop($trail);
        }

        return $trail;
    }

    /**
     * Identify the listen and the parameters that produced a step.
     *
     * @param  \LaraGram\Listening\Listen|null  $listen
     * @return string
     */
    protected function fingerprint(?Listen $listen): string
    {
        if ($listen === null) {
            return '';
        }

        return sha1(json_encode([
            $listen->getName() ?? [$listen->methods(), $listen->pattern()],
            $listen->hasParameters() ? $listen->parameters() : [],
        ]));
    }

    /**
     * Get the update of a request as an array.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return array
     */
    protected function updateOf(Request $request): array
    {
        return (array) json_decode(json_encode($request->update()), true);
    }

    /**
     * Get the Step Manager step the user is on.
     *
     * @return mixed
     */
    protected function currentState(): mixed
    {
        return $this->container->bound('step') ? $this->container->make('step')->get() : null;
    }

    /**
     * Put the user back on the Step Manager step a breadcrumb was recorded on.
     *
     * @param  mixed  $state
     * @return void
     */
    protected function restoreState(mixed $state): void
    {
        if (! $this->container->bound('step')) {
            return;
        }

        $state === null
            ? $this->container->make('step')->forget()
            : $this->container->make('step')->set($state);
    }

    /**
     * Make the given request the one being handled.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    protected function bindRequest(Request $request): void
    {
        $this->container->instance('request', $request);

        Facade::clearResolvedInstance('request');
    }

    /**
     * Get the request being handled.
     *
     * @return \LaraGram\Request\Request
     */
    protected function request(): Request
    {
        return $this->container->make('request');
    }

    /**
     * Get the cache key of the trail of the user being handled, in this chat of this bot.
     *
     * @return string|null
     */
    protected function key(): ?string
    {
        $request = $this->request();

        $user = sender()?->id;

        if ($user === null) {
            return null;
        }

        return implode(':', array_filter([
            $this->config->get('navigation.prefix', 'navigation'),
            $request->botConnection(),
            chat()?->id,
            $user,
        ], fn ($segment) => $segment !== null));
    }

    /**
     * Read a trail from the cache.
     *
     * @param  string  $key
     * @return list<\LaraGram\Listening\Navigation\Breadcrumb>
     */
    protected function load(string $key): array
    {
        return array_map(
            fn (array $data) => Breadcrumb::fromArray($data),
            (array) $this->store()->get($key, []),
        );
    }

    /**
     * Write a trail to the cache.
     *
     * @param  string  $key
     * @param  list<\LaraGram\Listening\Navigation\Breadcrumb>  $trail
     * @return void
     */
    protected function save(string $key, array $trail): void
    {
        $this->store()->put(
            $key,
            array_map(fn (Breadcrumb $crumb) => $crumb->toArray(), $trail),
            (int) $this->config->get('navigation.lifetime', 604800),
        );
    }

    /**
     * Get the cache store the trails are kept in.
     *
     * @return \LaraGram\Contracts\Cache\Repository
     */
    protected function store()
    {
        return $this->container->make('cache')->store($this->config->get('navigation.store'));
    }
}
